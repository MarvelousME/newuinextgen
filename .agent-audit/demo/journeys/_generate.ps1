$dir = "c:\Users\marvi\Downloads\wetransfer_newuinextgen_2026-07-07_1929\newuinextgen\.agent-audit\demo\journeys"

function New-JourneyFile {
  param($Spec, $Family, $Scenario = 'all')
  $stepObjs = @()
  foreach ($s in $Spec.steps) {
    $stepObjs += @{ action = $s }
  }
  $audit = if ($Spec.ContainsKey('audit') -and $Spec.audit) { $Spec.audit } else { @('demo_seed_completed') }
  $obj = [ordered]@{
    id                     = $Spec.id
    name                   = $Spec.name
    persona                = $Spec.persona
    scenario               = $Scenario
    family                 = $Family
    executable             = $true
    preconditions          = @('demo environment active', 'demo seed version 14.0.0')
    steps                  = $stepObjs
    expected_events        = $Spec.events
    expected_notifications = @('sandbox-notification')
    expected_integrations  = $Spec.integrations
    expected_audit_events  = $audit
    verification           = @{
      method           = "wp ngc demo_run_journey --id=$($Spec.id)"
      seed_graph_keys  = @($Spec.id)
      automated_test   = 'NGC_Demo_Journeys::run'
    }
  }
  $path = Join-Path $dir ($Spec.id + '.json')
  ($obj | ConvertTo-Json -Depth 8) | Set-Content -Path $path -Encoding utf8
}

$match = @(
  @{ id='MATCH-001'; name='Strong Match'; persona='demo-parent'; steps=@('request-match','rank-candidates','accept-match'); events=@('match.proposed','match.accepted'); integrations=@('crm-parent-sync','booking-sync') },
  @{ id='MATCH-002'; name='Online Alternative'; persona='demo-parent'; steps=@('request-match','filter-online','propose-online'); events=@('match.proposed'); integrations=@('crm-parent-sync') },
  @{ id='MATCH-003'; name='Availability Conflict'; persona='demo-parent'; steps=@('request-match','detect-conflict','suggest-alternatives'); events=@('match.proposed'); integrations=@('booking-sync') },
  @{ id='MATCH-004'; name='Budget Constraint'; persona='demo-parent'; steps=@('request-match','apply-budget-filter','propose-lower-cost'); events=@('match.proposed'); integrations=@('crm-parent-sync') },
  @{ id='MATCH-005'; name='Accessibility Requirement'; persona='demo-parent'; steps=@('request-match','filter-accessibility','propose-eligible'); events=@('match.proposed'); integrations=@('crm-parent-sync') },
  @{ id='MATCH-006'; name='Suspended Tutor Excluded'; persona='demo-parent'; steps=@('request-match','exclude-suspended','propose-eligible'); events=@('match.proposed'); integrations=@('crm-parent-sync') },
  @{ id='MATCH-007'; name='Manual Administrator Match'; persona='demo-admin'; steps=@('open-match-queue','manual-assign','notify-parties'); events=@('match.accepted'); integrations=@('crm-parent-sync','booking-sync'); audit=@('match_manual_assign') },
  @{ id='MATCH-008'; name='Tutor Rejection'; persona='demo-tutor-approved'; steps=@('receive-proposal','reject-match','process-next-candidate'); events=@('match.rejected','match.proposed'); integrations=@('crm-parent-sync') }
)

$book = @(
  @{ id='BOOK-001'; name='New Confirmed Booking'; persona='demo-parent'; steps=@('accept-match','select-slot','initiate-payment','confirm-booking'); events=@('booking.created','payment.succeeded','booking.confirmed'); integrations=@('crm-parent-sync','booking-sync','commerce-order') },
  @{ id='BOOK-002'; name='Recurring Booking'; persona='demo-parent'; steps=@('create-recurring','validate-conflicts','confirm-series'); events=@('booking.created','booking.confirmed'); integrations=@('booking-sync','commerce-order') },
  @{ id='BOOK-003'; name='Rescheduling'; persona='demo-parent'; steps=@('open-booking','reschedule','notify'); events=@('booking.rescheduled'); integrations=@('booking-sync') },
  @{ id='BOOK-004'; name='Cancellation Within Policy'; persona='demo-parent'; steps=@('cancel-booking','apply-policy','notify'); events=@('booking.cancelled'); integrations=@('booking-sync','commerce-order') },
  @{ id='BOOK-005'; name='Late Cancellation'; persona='demo-parent'; steps=@('late-cancel','apply-fee','notify'); events=@('booking.cancelled'); integrations=@('commerce-order') },
  @{ id='BOOK-006'; name='Tutor Cancellation'; persona='demo-tutor-approved'; steps=@('tutor-cancel','rebook-offer','notify'); events=@('booking.cancelled'); integrations=@('booking-sync','crm-parent-sync') },
  @{ id='BOOK-007'; name='No-Show'; persona='demo-tutor-approved'; steps=@('mark-no-show','apply-policy','notify'); events=@('booking.completed'); integrations=@('crm-parent-sync') },
  @{ id='BOOK-008'; name='Concurrent Booking Conflict'; persona='demo-parent'; steps=@('attempt-double-book','detect-conflict','reject-second'); events=@('booking.created'); integrations=@('booking-sync') }
)

$fin = @(
  @{ id='FIN-001'; name='Successful Payment'; persona='demo-parent'; steps=@('initiate-payfast-sandbox','receive-itn','confirm-order'); events=@('payment.succeeded','booking.confirmed'); integrations=@('commerce-order','crm-parent-sync') },
  @{ id='FIN-002'; name='Failed Payment'; persona='demo-parent'; steps=@('initiate-payment','fail-provider','retain-pending'); events=@('payment.failed'); integrations=@('commerce-order') },
  @{ id='FIN-003'; name='Duplicate Payment Webhook'; persona='system'; steps=@('replay-itn','assert-idempotent'); events=@('payment.succeeded'); integrations=@('commerce-order') },
  @{ id='FIN-004'; name='Partial Refund'; persona='demo-finance'; steps=@('issue-partial-refund','reconcile'); events=@('refund.completed'); integrations=@('commerce-order') },
  @{ id='FIN-005'; name='Full Refund'; persona='demo-finance'; steps=@('issue-full-refund','reconcile'); events=@('refund.completed'); integrations=@('commerce-order') },
  @{ id='FIN-006'; name='Wallet Top-Up and Usage'; persona='demo-parent'; steps=@('wallet-top-up','apply-to-booking'); events=@('payment.succeeded'); integrations=@('commerce-order') },
  @{ id='FIN-007'; name='Tutor Earning'; persona='demo-tutor-approved'; steps=@('complete-session','post-earning'); events=@('session.completed'); integrations=@('crm-parent-sync') },
  @{ id='FIN-008'; name='Tutor Payout'; persona='demo-finance'; steps=@('queue-payout','hold-sandbox'); events=@('payout.pending'); integrations=@('commerce-order') },
  @{ id='FIN-009'; name='Chargeback'; persona='demo-finance'; steps=@('record-chargeback','freeze-payout'); events=@('fraud.signal.raised'); integrations=@('commerce-order') },
  @{ id='FIN-010'; name='Reconciliation Difference'; persona='demo-finance'; steps=@('run-reconciliation','flag-difference'); events=@('finance.reconcile.diff'); integrations=@('commerce-order') }
)

foreach ($m in $match) { New-JourneyFile -Spec $m -Family 'matching' }
foreach ($b in $book) { New-JourneyFile -Spec $b -Family 'booking' }
foreach ($f in $fin) { New-JourneyFile -Spec $f -Family 'finance' }

$parent = [ordered]@{
  id='JOURNEY-PARENT-001'; name='Parent registers child and books tutor'; persona='demo-parent-new'; scenario='all'; family='umbrella'; executable=$true
  preconditions=@('demo environment active')
  steps=@(
    @{action='register-parent'},@{action='verify-email'},@{action='capture-consent'},@{action='create-child'},
    @{action='request-match'},@{action='accept-match'},@{action='create-booking'},@{action='complete-sandbox-payment'}
  )
  expected_events=@('ParentRegistered','child_learner.created','match.proposed','match.accepted','booking.confirmed','PaymentSucceeded')
  expected_notifications=@('child-profile-created','match-proposed','match-accepted','booking-confirmed','payment-receipt')
  expected_integrations=@('crm-parent-sync','lms-student-sync','booking-sync','commerce-order')
  expected_audit_events=@('consent-recorded','match_manual_assign','booking_confirmed','demo_seed_completed')
  covers=@('MATCH-001','BOOK-001','FIN-001','FIN-006')
  verification=@{ method='wp ngc demo_run_journey --id=JOURNEY-PARENT-001'; automated_test='NGC_Demo_Journeys::run' }
}
$tutor = [ordered]@{
  id='JOURNEY-TUTOR-001'; name='Tutor application through approval'; persona='demo-tutor-applicant'; scenario='all'; family='umbrella'; executable=$true
  preconditions=@('demo environment active')
  steps=@(
    @{action='submit-application'},@{action='admin-review'},@{action='approve-tutor'},@{action='sync-integrations'}
  )
  expected_events=@('tutor.application.submitted','tutor.approved')
  expected_notifications=@('tutor-application-received','tutor-approved')
  expected_integrations=@('crm-parent-sync','lms-student-sync','booking-sync')
  expected_audit_events=@('tutor_approved','demo_seed_completed')
  covers=@('MATCH-007')
  verification=@{ method='wp ngc demo_run_journey --id=JOURNEY-TUTOR-001'; automated_test='NGC_Demo_Journeys::run' }
}
$ops = [ordered]@{
  id='JOURNEY-OPS-001'; name='Ops fraud safeguarding and AI policy'; persona='demo-admin'; scenario='all'; family='umbrella'; executable=$true
  preconditions=@('demo environment active')
  steps=@(
    @{action='open-fraud-case'},@{action='open-safeguarding-case'},@{action='ai-deny'},@{action='ai-approve'},@{action='kill-switch'}
  )
  expected_events=@('fraud.signal.raised','safeguarding.alert.raised')
  expected_notifications=@('fraud-alert','safeguarding-alert')
  expected_integrations=@('crm-parent-sync')
  expected_audit_events=@('agent_policy_denied','agent_approval','agent_kill_switch','demo_seed_completed')
  covers=@('FIN-009')
  verification=@{ method='wp ngc demo_run_journey --id=JOURNEY-OPS-001'; automated_test='NGC_Demo_Journeys::run' }
}

($parent | ConvertTo-Json -Depth 8) | Set-Content (Join-Path $dir 'JOURNEY-PARENT-001.json') -Encoding utf8
($tutor | ConvertTo-Json -Depth 8) | Set-Content (Join-Path $dir 'JOURNEY-TUTOR-001.json') -Encoding utf8
($ops | ConvertTo-Json -Depth 8) | Set-Content (Join-Path $dir 'JOURNEY-OPS-001.json') -Encoding utf8

Write-Output ("journey_count=" + (Get-ChildItem $dir -Filter *.json).Count)
Get-ChildItem $dir -Filter *.json | Select-Object -ExpandProperty Name | Sort-Object
