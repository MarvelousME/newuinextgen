# 01 — Upstream TalentMatch-AI Analysis

**Source:** https://github.com/Kumkumrathor7078/TalentMatch-AI  
**Branch inspected:** `main`  
**Evidence date:** 2026-08-11  
**Method:** GitHub Contents API + raw `app.py` (not README alone)

## Repository shape (verified)

| Artifact | Present | Role |
|----------|---------|------|
| `app.py` | Yes | Streamlit UI + live `screen_candidate()` |
| `AI_Resume_Model.ipynb` (+ duplicates) | Yes (~2.7MB) | Training / offline analytics notebook |
| `requirements.txt` | Yes | **Broken/truncated:** `streamlit`, `pandas`, `numpy`, `scikit-learn`, then `jobli` + `b` (intended `joblib`) |
| `tfidf.pkl` | Yes (~216KB) | Fitted TF-IDF vectorizer (joblib) |
| `label_encoder.pkl` | Yes (~491B) | Loaded but **unused** in `screen_candidate()` |
| Multiple `*.csv` | Yes | Precomputed recruiter dashboards / rankings |
| Auth / DB / tests / Dockerfile | **No** | Absent |
| PDF/DOCX parsers | **No** | README lists as future; UI `type=["txt"]` only |

Language metadata: **Jupyter Notebook** (repo is notebook + Streamlit demo, not a service).

## Actual live scoring formula (from `app.py`)

```text
skill_score = |matched_skills ∩ required_skills| / |required_skills| × 100
              (0 if no required skills extracted)

text_similarity = cosine_similarity(tfidf(resume), tfidf(job)) × 100
                  (0 if tfidf.pkl missing / transform fails)

final_score = 0.60 × skill_score + 0.40 × text_similarity
```

### Recommendation thresholds (hard-coded)

| Final score | Label |
|-------------|-------|
| ≥ 75 | Highly Recommended |
| ≥ 60 | Recommended |
| ≥ 40 | Consider |
| else | Not Recommended |

### Evidence heuristic (hard-coded)

| Condition | Label |
|-----------|-------|
| ≥3 matched skills AND skill_score ≥ 75 | Strong Evidence |
| any match OR similarity ≥ 20 | Moderate Evidence |
| else | Weak Evidence |

## Skill extraction (verified)

- Hard-coded list `screening_skills` (~35 strings)
- Domain: **data science / cloud / ML engineering** (python, sql, tensorflow, kubernetes, aws, docker, …)
- Matching: substring `if skill in clean_text(text)` after lowercasing and regex scrub
- **Not** tutoring subjects, grades, curricula, CAPS/IEB, languages, delivery modes

## Text normalization

```text
lower → strip non [a-z0-9+#.\- ] → collapse whitespace
```

## Persistence model

- **Primary UI analytics** load static CSVs from repo root (`final_candidate_ranking.csv`, `skill_gap_analysis.csv`, …)
- Real-time screening returns an in-memory dict; optional CSV download of one row
- **No database**, no multi-tenant isolation, no audit trail

## Pickle / security

| Artifact | Used in live path? | Risk |
|----------|--------------------|------|
| `tfidf.pkl` | Yes (if present) | Deserializing joblib/pickle = code execution risk if untrusted |
| `label_encoder.pkl` | Loaded, **never referenced** in scoring | Dead artifact; still trust-boundary if loaded |

Do **not** accept user-uploaded pickles. Prefer checksum-pinned vendor artifacts or retrain into safer formats (JSON vocab + sparse matrix export).

## Training assumptions / leakage (notebook)

- Offline rankings appear trained/exported against a **generic tech-hiring corpus**, not NextGenTutors tutors
- CSV “explainable” panels largely echo matched/missing skill strings — not SHAP/LIME
- Duplicate notebooks (`AI_Resume_Model.ipynb`, `AI_Resume_Model 1.ipynb`, `AI_Resume_Model.ipynb.ipynb`) indicate experimental drift

## Error handling / tests

- Broad `except Exception: pass` on model load and TF-IDF transform → silent degradation to similarity=0
- **No** unit/contract/security tests in repo
- **No** input size limits beyond Streamlit defaults

## Runtime requirements

| Need | Reality |
|------|---------|
| Python + Streamlit | Required for upstream UI |
| scikit-learn + joblib | Required for TF-IDF path |
| Networked auth | None |
| Enterprise multi-tenant | None |

## What is actually reusable vs discardable

| Reuse (concepts) | Replace / discard |
|------------------|-------------------|
| Weighted skill-overlap + text similarity structure | Streamlit UI as production surface |
| Matched/missing skills + evidence labels | Tech skill vocabulary |
| Thresholded decision-support labels | CSV-as-database |
| Explainable “why” fields | Unused label encoder |
| Human keeps final hire decision (README intent) | Opaque single % without components |

## Verdict on upstream maturity

**Data-science / Streamlit recruitment prototype**, not an enterprise subsystem. Suitable as **algorithm inspiration**, not a drop-in service.
