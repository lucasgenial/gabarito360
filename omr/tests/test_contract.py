from pathlib import Path

from omr.process import process
from omr.scripts.generate_synthetic import OUTPUT, main as generate


ROOT = Path(__file__).parents[1]


def test_synthetic_contract_is_complete_and_requires_review_before_homologation() -> None:
    generate()
    result = process(OUTPUT, ROOT / "config" / "model-v1.pre-homologation.json")

    assert result["status"] == "partial"
    assert result["review_required"] is True
    assert "MODEL_NOT_HOMOLOGATED" in result["alerts"]
    assert len(result["responses"]) == 20
    assert [item["alternative"] for item in result["responses"]] == ["A", "B", "C", "D", "E"] * 4
    assert all(item["type"] == "marcada" for item in result["responses"])
