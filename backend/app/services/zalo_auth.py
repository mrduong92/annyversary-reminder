"""
Zalo authentication service.
Verifies Zalo access tokens and retrieves user info.
"""

import requests
from flask import current_app


def get_zalo_user_info(access_token: str) -> dict | None:
    """
    Use Zalo access token to get user profile.
    Returns dict with id, name, picture or None on failure.
    """
    try:
        resp = requests.get(
            "https://graph.zalo.me/v2.0/me",
            params={"fields": "id,name,picture"},
            headers={"access_token": access_token},
            timeout=10,
        )
        if resp.status_code == 200:
            data = resp.json()
            if "error" not in data or data.get("error") == 0:
                return {
                    "id": data.get("id"),
                    "name": data.get("name"),
                    "picture": data.get("picture", {}).get("data", {}).get("url"),
                }
    except requests.RequestException:
        pass

    return None


def verify_zalo_token(access_token: str) -> dict | None:
    """
    Verify Zalo Mini App access token.
    For Mini App, we use the token from zmp-sdk getAccessToken().
    Returns user info dict or None.
    """
    return get_zalo_user_info(access_token)
