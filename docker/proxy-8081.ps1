# Host reverse proxy: localhost:8081 -> Docker WordPress on 18080
# Needed because Docker Desktop holds a ghost listener on :8081 that blocks docker publish.
param(
  [int]$ListenPort = 8081,
  [int]$TargetPort = 18080,
  [string]$TargetHost = '127.0.0.1'
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Net.Http

$prefix = "http://127.0.0.1:$ListenPort/"
$listener = [System.Net.HttpListener]::new()
$listener.Prefixes.Add($prefix)
$listener.Prefixes.Add("http://localhost:$ListenPort/")
try {
  $listener.Start()
} catch {
  # Fallback: try + without URL ACL by using TcpListener raw forward (simpler HTTP/1.0)
  Write-Host "HttpListener failed ($($_.Exception.Message)); using TcpListener forwarder"
  $tcp = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Loopback, $ListenPort)
  $tcp.Start()
  Write-Host "Proxy listening on 127.0.0.1:$ListenPort -> ${TargetHost}:${TargetPort}"
  while ($true) {
    $client = $tcp.AcceptTcpClient()
    [void][System.Threading.ThreadPool]::QueueUserWorkItem({
      param($state)
      $c = $state
      try {
        $upstream = [System.Net.Sockets.TcpClient]::new()
        $upstream.Connect($TargetHost, $TargetPort)
        $cs = $c.GetStream(); $us = $upstream.GetStream()
        $buf = New-Object byte[] 8192
        # bidirectional copy with short loops
        $c.ReceiveTimeout = 30000; $upstream.ReceiveTimeout = 30000
        while ($c.Connected -and $upstream.Connected) {
          $copied = $false
          if ($cs.DataAvailable) {
            $n = $cs.Read($buf, 0, $buf.Length)
            if ($n -le 0) { break }
            $us.Write($buf, 0, $n); $copied = $true
          }
          if ($us.DataAvailable) {
            $n = $us.Read($buf, 0, $buf.Length)
            if ($n -le 0) { break }
            $cs.Write($buf, 0, $n); $copied = $true
          }
          if (-not $copied) { Start-Sleep -Milliseconds 5 }
        }
      } catch {}
      finally {
        try { $c.Close() } catch {}
        try { $upstream.Close() } catch {}
      }
    }, $client)
  }
  return
}

Write-Host "HttpListener proxy $prefix -> http://${TargetHost}:${TargetPort}/"
$client = [System.Net.Http.HttpClient]::new()
while ($listener.IsListening) {
  $ctx = $listener.GetContext()
  try {
    $req = $ctx.Request
    $resp = $ctx.Response
    $path = $req.Url.PathAndQuery
    $targetUri = "http://${TargetHost}:${TargetPort}${path}"
    $msg = [System.Net.Http.HttpRequestMessage]::new([System.Net.Http.HttpMethod]::new($req.HttpMethod), $targetUri)
    if ($req.HasEntityBody) {
      $ms = New-Object System.IO.MemoryStream
      $req.InputStream.CopyTo($ms)
      $msg.Content = [System.Net.Http.ByteArrayContent]::new($ms.ToArray())
      if ($req.ContentType) { $msg.Content.Headers.TryAddWithoutValidation('Content-Type', $req.ContentType) | Out-Null }
    }
    foreach ($h in $req.Headers.AllKeys) {
      if ($h -in @('Host','Content-Length','Connection')) { continue }
      [void]$msg.Headers.TryAddWithoutValidation($h, $req.Headers[$h])
    }
    $upstream = $client.SendAsync($msg).GetAwaiter().GetResult()
    $resp.StatusCode = [int]$upstream.StatusCode
    foreach ($h in $upstream.Headers) {
      try { $resp.Headers[$h.Key] = ($h.Value -join ', ') } catch {}
    }
    if ($upstream.Content) {
      foreach ($h in $upstream.Content.Headers) {
        if ($h.Key -eq 'Content-Length') { continue }
        try { $resp.Headers[$h.Key] = ($h.Value -join ', ') } catch {}
      }
      $bytes = $upstream.Content.ReadAsByteArrayAsync().GetAwaiter().GetResult()
      $resp.ContentLength64 = $bytes.Length
      $resp.OutputStream.Write($bytes, 0, $bytes.Length)
    }
    $resp.Close()
  } catch {
    try {
      $ctx.Response.StatusCode = 502
      $bytes = [Text.Encoding]::UTF8.GetBytes("Bad gateway: $($_.Exception.Message)")
      $ctx.Response.OutputStream.Write($bytes, 0, $bytes.Length)
      $ctx.Response.Close()
    } catch {}
  }
}
