# WF-11: Payment Workflow

**Evidence:** PARTIAL  
**Theme version:** 1.4.6

## BPMN Specification

| Field | Value |
|-------|-------|
| **Trigger** | woocommerce.order.completed |
| **Preconditions** | Woo active |
| **Actor** | Parent / system |
| **Steps** | Dispatch payment workflow |
| **Decisions** | — |
| **Exceptions** | No failed path |
| **Escalations** | payment-issues |
| **Outputs** | RTM alerts |
| **DB changes** | bi_rtm_queue |
| **Notifications** | RTM |
| **Audit** | woocommerce_payment_completed |

## Code anchors

- inc/shortcodes-fallback.php — form intake
- inc/workflows.php — dispatcher
- inc/security.php — dashboard access (WF-25)
- inc/openwa.php — WhatsApp (WF-23)
