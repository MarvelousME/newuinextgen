# RBAC — Beyond Measure

## Capabilities (atoms)

See `NGTBM\Domain\Authorization\CapabilityCatalog::ALL`.

## Roles (bundles)

| Role slug | Label |
|-----------|--------|
| ngt_platform_admin | NGT Platform Administrator |
| ngt_ops_manager | NGT Operations Manager |
| ngt_tutor_manager | NGT Tutor Manager |
| ngt_safeguarding | NGT Safeguarding Officer |
| ngt_finance_manager | NGT Finance Manager |
| ngt_crm_manager | NGT CRM Manager |
| ngt_ai_admin | NGT AI Administrator |
| ngt_auditor | NGT Auditor |
| ngt_support | NGT Support |

Authorization never uses `$user->roles[0] === 'administrator'`. Flow: WP cap → optional `NGC_Authz_Matrix` → object context (e.g. safeguarding LOCKED) → optional `NGC_Policy_Bridge`.
