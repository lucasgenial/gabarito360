from pathlib import Path

import cv2
import numpy as np


OUTPUT = Path(__file__).parents[1] / "dataset" / "regression" / "synthetic-card.png"
ANSWERS = ["A", "B", "C", "D", "E"]
CENTERS_X = {"A": 900, "B": 1120, "C": 1340, "D": 1560, "E": 1780}


def main() -> None:
    image = np.full((3508, 2480, 3), 255, dtype=np.uint8)

    for question in range(1, 21):
        y = 950 + (question - 1) * 105
        expected = ANSWERS[(question - 1) % len(ANSWERS)]
        for alternative, x in CENTERS_X.items():
            cv2.circle(image, (x, y), 42, (0, 0, 0), 4)
            if alternative == expected:
                cv2.circle(image, (x, y), 28, (0, 0, 0), -1)

    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    cv2.imwrite(str(OUTPUT), image)
    print(OUTPUT)


if __name__ == "__main__":
    main()
