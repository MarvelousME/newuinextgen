<?php
$zip = new ZipArchive();
$path = __DIR__ . '/dist/NextGenTutors-BeyondInfinity.zip';
echo "open=" . $zip->open($path) . "\n";
$tmp = sys_get_temp_dir() . '/ngt-php-theme-test';
if (is_dir($tmp)) {
  $it = new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS);
  $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
  foreach ($files as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
  @rmdir($tmp);
}
mkdir($tmp);
$zip->extractTo($tmp);
$zip->close();
$children = array_values(array_diff(scandir($tmp), ['.', '..']));
echo "root children: " . implode(', ', $children) . "\n";
$theme = $tmp . '/' . $children[0];
echo "style exists: " . (file_exists($theme . '/style.css') ? 'yes' : 'no') . "\n";
if (file_exists($theme . '/style.css')) {
  $h = get_file_data($theme . '/style.css', ['Name' => 'Theme Name', 'Template' => 'Template', 'Version' => 'Version']);
  print_r($h);
}
