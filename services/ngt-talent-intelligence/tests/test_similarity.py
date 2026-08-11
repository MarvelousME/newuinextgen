from app.main import cosine_bag


def test_identical_high():
    assert cosine_bag("math physics teaching", "math physics teaching") > 99


def test_empty_zero():
    assert cosine_bag("", "math") == 0.0


def test_partial():
    score = cosine_bag("mathematics grade 12 caps", "mathematics online tutor")
    assert 0 < score < 100
