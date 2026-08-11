"""
NextGen Tutors Talent Intelligence — optional text similarity sidecar.

Internal network only. Not an LLM gateway. No Streamlit.
"""

from __future__ import annotations

import math
import re
from collections import Counter
from typing import Dict, List

from fastapi import FastAPI
from pydantic import BaseModel, Field

app = FastAPI(title="NGT Talent NLP", version="1.0.0")

TOKEN_RE = re.compile(r"[a-z0-9+#.\-]+")


def tokenize(text: str) -> List[str]:
    return TOKEN_RE.findall((text or "").lower())


def cosine_bag(a: str, b: str) -> float:
    ta, tb = tokenize(a), tokenize(b)
    if not ta or not tb:
        return 0.0
    ca, cb = Counter(ta), Counter(tb)
    keys = set(ca) | set(cb)
    dot = sum(ca[k] * cb[k] for k in keys)
    na = math.sqrt(sum(v * v for v in ca.values()))
    nb = math.sqrt(sum(v * v for v in cb.values()))
    if na == 0 or nb == 0:
        return 0.0
    return (dot / (na * nb)) * 100.0


class SimilarityRequest(BaseModel):
    text_a: str = Field(default="")
    text_b: str = Field(default="")


class SimilarityResponse(BaseModel):
    similarity: float
    modelVersion: str = "ngt-talent-nlp-bow-v1"


@app.get("/health")
@app.get("/v1/health")
def health() -> Dict[str, object]:
    return {"ok": True, "service": "ngt-talent-intelligence", "auto_approve_forbidden": True}


@app.get("/ready")
@app.get("/v1/ready")
def ready() -> Dict[str, object]:
    return {"ready": True}


@app.post("/v1/similarity", response_model=SimilarityResponse)
def similarity(body: SimilarityRequest) -> SimilarityResponse:
    score = cosine_bag(body.text_a, body.text_b)
    return SimilarityResponse(similarity=round(score, 2))
