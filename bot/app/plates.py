CYRILLIC_TO_LATIN = {
    "А": "A",
    "В": "B",
    "Е": "E",
    "К": "K",
    "М": "M",
    "Н": "H",
    "О": "O",
    "Р": "P",
    "С": "C",
    "Т": "T",
    "У": "Y",
    "Х": "X",
}


def normalize_plate(text: str) -> str:
    clean = text.replace(" ", "").replace("-", "").upper()
    return "".join(CYRILLIC_TO_LATIN.get(ch, ch) for ch in clean)
