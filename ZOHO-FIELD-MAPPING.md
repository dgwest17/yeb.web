# Zoho CRM Leads — Field Mapping Reference (Phase 4)

## Standard Fields
| Website Field | Zoho Display Name | Zoho API Name | Type | Notes |
|--------------|-------------------|---------------|------|-------|
| Full Name (first part) | First Name | First_Name | Single Line | Split on first space |
| Full Name (remainder) | Last Name | Last_Name | Single Line | Required — use "(not provided)" if blank |
| Email | Email | Email | Email | Used for upsert dedup |
| Phone | Phone | Phone | Phone | |
| (auto) | Lead Source | Lead_Source | Picklist | "Website" / "Website - Newsletter" / "Website - Pay" |
| (auto) | Lead Status | Lead_Status | Picklist | "New" (only on create) |

## Custom Fields
| Website Field | Zoho Display Name | Zoho API Name (VERIFY) | Type | Notes |
|--------------|-------------------|------------------------|------|-------|
| Zip Code | Zip Code | Zip_Code | Single Line | |
| Bill Slider | Avg Monthly Bill | Avg_Monthly_Bill | Currency | Numeric value from slider |
| Card Selection | Opportunity Type | Opportunity_Type | Picklist | See mapping below |
| (auto) | Newsletter Subscriber | Newsletter_Subscriber | Boolean | Always true for all forms |

## Opportunity Type Mapping
| Website Selection | Zoho Opportunity Type Value |
|------------------|---------------------------|
| "New to Solar" | No Solar Yet |
| "$0 Down Financing" | No Solar Yet |
| "Ready for Proposal" | No Solar - Bid Searching |
| "Already Have Solar" | Solar Owner – Audit / Review |
| Pay: Audit/Review | Solar Owner – Audit / Review |
| Pay: Service/Repair | Solar Owner – Service / Repair |
| Pay: Service Plan | Solar Owner – Under Service Plan |

## IMPORTANT: API Name Verification Needed
The API names above use underscore convention (e.g., `Zip_Code`, `Avg_Monthly_Bill`).
However, Zoho custom field API names sometimes differ. For example:
- Display: "Avg Monthly Bill" → API might be: `Avg_Monthly_Bill` or `Avg_monthly_Bill` or something else

To verify, David can check: Zoho CRM → Settings → Modules → Leads → Fields → click any field → "Field Properties" shows the API name.

Alternatively, we can test with a submission and check zoho_debug.log for any field rejection errors.
