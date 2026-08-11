# Optional Talent NLP profile

Internal cosine bag-of-words similarity for free-text bio ↔ requirement narrative.

```bash
docker compose --profile talent -f docker-compose.yml -f talent/docker-compose.talent.yml up -d talent-nlp
```

Point Memory-style setting `nlp_sidecar_url` to `http://talent-nlp:8090` (compose) or `http://127.0.0.1:8090`.

- Not an LLM gateway
- Not Streamlit
- Not required for Bridge-native suitability scoring
- Does not approve tutors
