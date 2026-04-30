# R2 Setup Reference

## GitHub Actions Secrets

Add these secrets in GitHub → repo Settings → Secrets and variables → Actions:

| Secret | Value |
|--------|-------|
| `R2_ACCOUNT_ID` | Cloudflare account ID (found in dashboard right sidebar) |
| `R2_BUCKET` | R2 bucket name (e.g. `optica-store-assets`) |
| `R2_ACCESS_KEY_ID` | R2 API token Access Key ID |
| `R2_SECRET_ACCESS_KEY` | R2 API token Secret Access Key |
| `ASSET_URL` | Public URL of the R2 bucket or custom domain (e.g. `https://assets.example.com`) |

## Creating R2 API Credentials

1. Cloudflare dashboard → R2 → Manage R2 API tokens
2. Create token → select "Object Read & Write" on the target bucket
3. Copy the Access Key ID and Secret Access Key — they are shown once

## Applying CORS Policy

Replace `your-app-domain.com` in `infra/r2-cors.json` with the actual production domain, then apply:

```bash
wrangler r2 bucket cors put BUCKET_NAME --file infra/r2-cors.json
```

Or via Cloudflare dashboard → R2 → bucket → Settings → CORS.
