# NextGen Tutors - Step-by-Step Tutorial

This tutorial covers the primary features of the NextGen Tutors plugin. 

## 1. Navigating Mission Control
Mission Control is the central hub for the platform.
- **Overview:** View high-level metrics for revenue and student signups.
- **Analytics:** Access detailed Chart.js visual drilldowns of your earnings history over the last 6 months.
- **Integrations:** Check the status of required plugins (Masterstudy LMS, FluentCRM, WooCommerce, PayFast, etc.). If any are missing, click **Install & Activate**.
- **Settings:** Access global configurations and download this tutorial.

## 2. Setting Up Triggers
Triggers allow you to hook into WordPress events dynamically without touching code.
1. Go to the **Triggers** tab.
2. Under "Add Trigger", enter a **Name** (e.g., "On User Signup").
3. Enter the **WP Hook** (e.g., `user_register`).
4. Enter the **Callback**. You can enter a standard PHP function name (e.g., `wp_mail`) OR you can route it to a Workflow by typing `workflow:ID` (e.g., `workflow:1`).
5. Click **Create Trigger**.

## 3. Configuring Workflows
Workflows execute a series of steps via JSON.
1. Go to the **Workflows** tab.
2. Give the workflow a **Name**.
3. Under Steps, provide a JSON array of actions. Example:
```json
[
  {
    "type": "log",
    "message": "New user registered via workflow!"
  },
  {
    "type": "email",
    "to": "admin@yourdomain.com",
    "subject": "New Signup",
    "message": "A new user has just registered."
  }
]
```
4. Click **Create Workflow**. If a Trigger is hooked to this workflow ID, these steps will execute automatically.

## 4. Managing Payouts
The plugin includes a batch processing engine to pay tutors via PayFast.
1. Navigate to the **Overview** tab.
2. Click the **Run Payout Batch** button.
3. The system queries the database for `pending` payouts and automatically securely requests transfers via the PayFast API using your configured Merchant ID and Passphrase.

---
*For further support or issues, please refer to the integration specifications in the main repository or contact the admin team.*
