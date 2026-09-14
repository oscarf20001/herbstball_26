def generate_comment_box(text: str, width: int = 75):
    if width < len(text) + 2:
        raise ValueError("Box is too narrow for the text.")

    border = "=" * width
    inner_width = width - 2  # exclude the | borders

    # Exakte Zentrierung des Textes
    left_padding = (inner_width - len(text)) // 2
    right_padding = inner_width - len(text) - left_padding
    centered_line = "|" + (" " * left_padding) + text + (" " * right_padding) + "|"

    empty_line = "|" + (" " * inner_width) + "|"

    box = [
        "<!--",
        border,
        empty_line,
        centered_line,
        empty_line,
        border,
        "-->"
    ]
    return "\n".join(box)

# Beispiel
print(generate_comment_box(input("Bitte gib deinen Comment-Wunsch hier ein:\n")))