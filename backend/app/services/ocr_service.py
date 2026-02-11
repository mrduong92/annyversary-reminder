"""
OCR service using OpenAI GPT-4o Vision to extract anniversary data from images.
"""

import json
from openai import OpenAI
from flask import current_app


def extract_anniversaries_from_image(image_base64: str) -> list[dict]:
    """
    Send image to GPT-4o Vision and extract death anniversary information.
    Returns list of dicts with keys: person_name, relationship, lunar_day, lunar_month, notes
    """
    client = OpenAI(api_key=current_app.config["OPENAI_API_KEY"])

    response = client.chat.completions.create(
        model="gpt-4o",
        messages=[
            {
                "role": "system",
                "content": (
                    "You are an expert at reading Vietnamese death anniversary (ngày giỗ) lists. "
                    "Extract all death anniversary information from the image. "
                    "Return a JSON array where each element has: "
                    "person_name (string, Vietnamese name), "
                    "relationship (string, e.g. 'Ông nội', 'Bà ngoại', 'Bác', etc.), "
                    "lunar_day (integer 1-30), "
                    "lunar_month (integer 1-12), "
                    "notes (string, any additional info). "
                    "Only return valid JSON array, no markdown or explanation."
                ),
            },
            {
                "role": "user",
                "content": [
                    {
                        "type": "text",
                        "text": "Extract death anniversary information from this Vietnamese image. Return JSON array only.",
                    },
                    {
                        "type": "image_url",
                        "image_url": {
                            "url": f"data:image/jpeg;base64,{image_base64}",
                        },
                    },
                ],
            },
        ],
        max_tokens=2000,
        temperature=0,
    )

    content = response.choices[0].message.content.strip()
    # Strip markdown code block if present
    if content.startswith("```"):
        content = content.split("\n", 1)[1] if "\n" in content else content[3:]
        if content.endswith("```"):
            content = content[:-3]
        content = content.strip()

    try:
        result = json.loads(content)
        if isinstance(result, list):
            return result
    except json.JSONDecodeError:
        pass

    return []
