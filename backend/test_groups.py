"""
Test script for Anniversary Groups feature.
Uses PostgreSQL when DATABASE_URL is set, otherwise SQLite in-memory.
Flask test client — no browser needed.

Run: python test_groups.py
"""

import os
import sys
import json
import jwt
from datetime import datetime, timedelta, timezone

# Fallback to SQLite if no DATABASE_URL provided
if not os.environ.get("DATABASE_URL"):
    os.environ["DATABASE_URL"] = "sqlite:///:memory:"
if not os.environ.get("SECRET_KEY"):
    os.environ["SECRET_KEY"] = "test-secret-key"
if not os.environ.get("OPENAI_API_KEY"):
    os.environ["OPENAI_API_KEY"] = "fake"
if not os.environ.get("ZALO_APP_ID"):
    os.environ["ZALO_APP_ID"] = "test-app"
if not os.environ.get("ZALO_APP_SECRET"):
    os.environ["ZALO_APP_SECRET"] = "test-secret"

from app import create_app, db
from app.models.user import User
from app.models.anniversary import Anniversary
from app.models.anniversary_group import AnniversaryGroup
from app.models.group_subscription import GroupSubscription
from app.models.reminder import Reminder


def make_token(user_id):
    """Create a JWT token for testing."""
    return jwt.encode(
        {"user_id": user_id, "exp": datetime.now(timezone.utc) + timedelta(hours=1)},
        os.environ.get("SECRET_KEY", "test-secret-key"),
        algorithm="HS256",
    )


def auth_header(token):
    return {"Authorization": f"Bearer {token}", "Content-Type": "application/json"}


def test_groups():
    app = create_app()

    # Disable scheduler for testing
    app.config["TESTING"] = True

    with app.app_context():
        # Create tables if they don't exist (for SQLite mode)
        db.create_all()

        # Clean up test data from previous runs
        from app.models.chat_message import ChatMessage
        from app.models.default_event import DefaultEvent
        Reminder.query.delete()
        Anniversary.query.delete()
        GroupSubscription.query.delete()
        AnniversaryGroup.query.delete()
        ChatMessage.query.delete()
        DefaultEvent.query.delete()
        User.query.delete()
        db.session.commit()

        # ============================
        # Setup: Create two test users
        # ============================
        user1 = User(zalo_uid="user1_zalo", display_name="Nguyen Van A", avatar_url="https://example.com/avatar1.jpg")
        user2 = User(zalo_uid="user2_zalo", display_name="Tran Thi B", avatar_url="https://example.com/avatar2.jpg")
        db.session.add_all([user1, user2])
        db.session.commit()

        token1 = make_token(user1.id)
        token2 = make_token(user2.id)

        client = app.test_client()

        print("=" * 60)
        print("ANNIVERSARY GROUPS - TEST SUITE")
        print("=" * 60)
        passed = 0
        failed = 0

        def check(name, condition, detail=""):
            nonlocal passed, failed
            if condition:
                print(f"  [PASS] {name}")
                passed += 1
            else:
                print(f"  [FAIL] {name} {detail}")
                failed += 1

        # ============================
        # 1. Create default group for user1
        # ============================
        print("\n--- 1. Default Group Creation ---")
        from app.services.group_service import create_default_group, get_or_create_default_group
        default_group = create_default_group(user1.id)
        db.session.commit()
        check("Default group created", default_group is not None)
        check("Default group name is 'Chung'", default_group.name == "Chung")
        check("Default group is_default=True", default_group.is_default is True)

        # Create default for user2 too
        default_group2 = create_default_group(user2.id)
        db.session.commit()

        # ============================
        # 2. List groups (should have default)
        # ============================
        print("\n--- 2. List Groups ---")
        resp = client.get("/api/v1/groups", headers=auth_header(token1))
        check("GET /groups returns 200", resp.status_code == 200)
        data = resp.get_json()
        check("Has 'owned' key", "owned" in data)
        check("Has 'subscribed' key", "subscribed" in data)
        check("Owned has 1 group", len(data["owned"]) == 1)
        check("First owned group is 'Chung'", data["owned"][0]["name"] == "Chung")
        check("Subscribed is empty", len(data["subscribed"]) == 0)

        # ============================
        # 3. Create additional groups
        # ============================
        print("\n--- 3. Create Groups ---")
        resp = client.post("/api/v1/groups", headers=auth_header(token1),
                           data=json.dumps({"name": "Giỗ bên Nội"}))
        check("POST /groups returns 201", resp.status_code == 201)
        noi_group = resp.get_json()
        check("Group name correct", noi_group["name"] == "Giỗ bên Nội")
        check("Group is_default=False", noi_group["is_default"] is False)

        resp = client.post("/api/v1/groups", headers=auth_header(token1),
                           data=json.dumps({"name": "Giỗ bên Ngoại"}))
        check("Second group created", resp.status_code == 201)
        ngoai_group = resp.get_json()

        # Verify list now has 3
        resp = client.get("/api/v1/groups", headers=auth_header(token1))
        data = resp.get_json()
        check("Now has 3 owned groups", len(data["owned"]) == 3)

        # ============================
        # 4. Rename group
        # ============================
        print("\n--- 4. Rename Group ---")
        resp = client.put(f"/api/v1/groups/{noi_group['id']}", headers=auth_header(token1),
                          data=json.dumps({"name": "Giỗ bên Nội (updated)"}))
        check("PUT rename returns 200", resp.status_code == 200)
        check("Name updated", resp.get_json()["name"] == "Giỗ bên Nội (updated)")

        # Rename back
        client.put(f"/api/v1/groups/{noi_group['id']}", headers=auth_header(token1),
                   data=json.dumps({"name": "Giỗ bên Nội"}))

        # ============================
        # 5. Add anniversaries to groups
        # ============================
        print("\n--- 5. Anniversaries with Groups ---")

        # Add to default group (no group_id)
        resp = client.post("/api/v1/anniversaries", headers=auth_header(token1),
                           data=json.dumps({
                               "person_name": "Ông Nội",
                               "relationship": "Ông nội",
                               "lunar_day": 15,
                               "lunar_month": 3,
                           }))
        check("Anniversary created (default group)", resp.status_code == 201)
        ann1 = resp.get_json()
        check("Has group_id", ann1.get("group_id") == default_group.id,
              f"expected {default_group.id}, got {ann1.get('group_id')}")
        check("Has group_name 'Chung'", ann1.get("group_name") == "Chung")

        # Add to specific group
        resp = client.post("/api/v1/anniversaries", headers=auth_header(token1),
                           data=json.dumps({
                               "person_name": "Bà Nội",
                               "relationship": "Bà nội",
                               "lunar_day": 10,
                               "lunar_month": 7,
                               "group_id": noi_group["id"],
                           }))
        check("Anniversary with group_id", resp.status_code == 201)
        ann2 = resp.get_json()
        check("group_id matches", ann2.get("group_id") == noi_group["id"])
        check("group_name matches", ann2.get("group_name") == "Giỗ bên Nội")

        # Add more to Nội group
        resp = client.post("/api/v1/anniversaries", headers=auth_header(token1),
                           data=json.dumps({
                               "person_name": "Ông Cố Nội",
                               "relationship": "Khác",
                               "lunar_day": 5,
                               "lunar_month": 1,
                               "group_id": noi_group["id"],
                           }))
        check("Third anniversary created", resp.status_code == 201)

        # Add to Ngoại group
        resp = client.post("/api/v1/anniversaries", headers=auth_header(token1),
                           data=json.dumps({
                               "person_name": "Ông Ngoại",
                               "relationship": "Ông ngoại",
                               "lunar_day": 20,
                               "lunar_month": 9,
                               "group_id": ngoai_group["id"],
                           }))
        check("Ngoai group anniversary created", resp.status_code == 201)

        # ============================
        # 6. Filter anniversaries by group
        # ============================
        print("\n--- 6. Filter Anniversaries by Group ---")

        # All anniversaries
        resp = client.get("/api/v1/anniversaries", headers=auth_header(token1))
        all_anns = resp.get_json()
        check("Total anniversaries = 4", len(all_anns) == 4, f"got {len(all_anns)}")

        # Filter by Nội group
        resp = client.get(f"/api/v1/anniversaries?group_id={noi_group['id']}", headers=auth_header(token1))
        noi_anns = resp.get_json()
        check("Nội group has 2 anniversaries", len(noi_anns) == 2, f"got {len(noi_anns)}")

        # Filter by Ngoại group
        resp = client.get(f"/api/v1/anniversaries?group_id={ngoai_group['id']}", headers=auth_header(token1))
        ngoai_anns = resp.get_json()
        check("Ngoại group has 1 anniversary", len(ngoai_anns) == 1, f"got {len(ngoai_anns)}")

        # All have is_editable = True (own anniversaries)
        check("Own anniversaries are editable", all(a.get("is_editable") for a in all_anns))

        # ============================
        # 7. Share group
        # ============================
        print("\n--- 7. Share Group ---")
        resp = client.post(f"/api/v1/groups/{noi_group['id']}/share", headers=auth_header(token1))
        check("POST share returns 200", resp.status_code == 200)
        share_data = resp.get_json()
        share_code = share_data["share_code"]
        check("share_code is 8 chars", len(share_code) == 8, f"got '{share_code}' ({len(share_code)} chars)")
        print(f"       Share code: {share_code}")

        # Share again → returns same code
        resp = client.post(f"/api/v1/groups/{noi_group['id']}/share", headers=auth_header(token1))
        check("Re-share returns same code", resp.get_json()["share_code"] == share_code)

        # ============================
        # 8. Preview shared group (no auth)
        # ============================
        print("\n--- 8. Preview Shared Group ---")
        resp = client.get(f"/api/v1/groups/preview?share_code={share_code}")
        check("GET preview returns 200", resp.status_code == 200)
        preview = resp.get_json()
        check("Preview shows group name", preview["name"] == "Giỗ bên Nội")
        check("Preview shows anniversary count = 2", preview["anniversary_count"] == 2)
        check("Preview shows owner name", preview["owner_name"] == "Nguyen Van A")
        print(f"       Preview: {preview['name']} by {preview['owner_name']} ({preview['anniversary_count']} ngày giỗ)")

        # Invalid code
        resp = client.get("/api/v1/groups/preview?share_code=INVALID1")
        check("Invalid code returns 404", resp.status_code == 404)

        # ============================
        # 9. User2 joins shared group
        # ============================
        print("\n--- 9. Join Group ---")

        # User2 cannot subscribe to own group (different test, but let's test subscribing)
        resp = client.post("/api/v1/groups/join", headers=auth_header(token2),
                           data=json.dumps({"share_code": share_code}))
        check("POST join returns 201", resp.status_code == 201)
        join_data = resp.get_json()
        check("Subscription created", "subscription" in join_data)
        check("Group info returned", join_data["group"]["name"] == "Giỗ bên Nội")

        # Duplicate join → 409
        resp = client.post("/api/v1/groups/join", headers=auth_header(token2),
                           data=json.dumps({"share_code": share_code}))
        check("Duplicate join returns 409", resp.status_code == 409)

        # Owner cannot join own group
        resp = client.post("/api/v1/groups/join", headers=auth_header(token1),
                           data=json.dumps({"share_code": share_code}))
        check("Owner cannot join own group (400)", resp.status_code == 400)

        # ============================
        # 10. User2 sees subscribed group
        # ============================
        print("\n--- 10. Subscriber Sees Group ---")
        resp = client.get("/api/v1/groups", headers=auth_header(token2))
        data = resp.get_json()
        check("User2 has 1 owned group", len(data["owned"]) == 1, f"got {len(data['owned'])}")
        check("User2 has 1 subscribed group", len(data["subscribed"]) == 1, f"got {len(data['subscribed'])}")
        check("Subscribed group is 'Giỗ bên Nội'", data["subscribed"][0]["name"] == "Giỗ bên Nội")
        check("Shows owner name", data["subscribed"][0].get("owner_name") == "Nguyen Van A")

        # User2 sees subscribed group's anniversaries
        resp = client.get(f"/api/v1/anniversaries?group_id={noi_group['id']}", headers=auth_header(token2))
        sub_anns = resp.get_json()
        check("User2 sees 2 subscribed anniversaries", len(sub_anns) == 2, f"got {len(sub_anns)}")
        check("Subscribed anniversaries are NOT editable", all(a.get("is_editable") is False for a in sub_anns))

        # User2 sees all (own + subscribed) when no filter
        resp = client.get("/api/v1/anniversaries", headers=auth_header(token2))
        all_user2 = resp.get_json()
        check("User2 sees own + subscribed", len(all_user2) == 2, f"got {len(all_user2)} (0 own + 2 subscribed)")

        # ============================
        # 11. Subscribers list (owner only)
        # ============================
        print("\n--- 11. Subscribers ---")
        resp = client.get(f"/api/v1/groups/{noi_group['id']}/subscribers", headers=auth_header(token1))
        check("GET subscribers returns 200", resp.status_code == 200)
        subs = resp.get_json()
        check("1 subscriber", len(subs) == 1, f"got {len(subs)}")
        check("Subscriber is User2", subs[0]["display_name"] == "Tran Thi B")

        # Non-owner cannot list subscribers
        resp = client.get(f"/api/v1/groups/{noi_group['id']}/subscribers", headers=auth_header(token2))
        check("Non-owner cannot list subscribers (404)", resp.status_code == 404)

        # ============================
        # 12. Reminder materialization for subscribers
        # ============================
        print("\n--- 12. Subscriber Reminders ---")
        from app.services.reminder_service import materialize_reminders_for_subscriber
        # Check reminders created for user2 from subscribed group
        reminders = Reminder.query.filter_by(user_id=user2.id).all()
        check("User2 has reminders from subscription", len(reminders) > 0,
              f"got {len(reminders)} reminders")
        if reminders:
            print(f"       User2 has {len(reminders)} reminders from subscribed group")

        # ============================
        # 13. User2 can create anniversary in own group
        # ============================
        print("\n--- 13. Subscriber Cannot Edit Shared, Can Edit Own ---")
        resp = client.post("/api/v1/anniversaries", headers=auth_header(token2),
                           data=json.dumps({
                               "person_name": "Bà Ngoại (User2)",
                               "relationship": "Bà ngoại",
                               "lunar_day": 8,
                               "lunar_month": 5,
                           }))
        check("User2 can create own anniversary", resp.status_code == 201)
        user2_ann = resp.get_json()
        check("User2 anniversary in default group", user2_ann["group_name"] == "Chung")

        # User2 cannot edit user1's anniversary
        resp = client.put(f"/api/v1/anniversaries/{ann2['id']}", headers=auth_header(token2),
                          data=json.dumps({"person_name": "HACKED"}))
        check("User2 cannot edit User1's anniversary (404)", resp.status_code == 404)

        # User2 cannot delete user1's anniversary
        resp = client.delete(f"/api/v1/anniversaries/{ann2['id']}", headers=auth_header(token2))
        check("User2 cannot delete User1's anniversary (404)", resp.status_code == 404)

        # ============================
        # 14. Unsubscribe
        # ============================
        print("\n--- 14. Unsubscribe ---")

        # First share ngoai group too for testing
        resp = client.post(f"/api/v1/groups/{ngoai_group['id']}/share", headers=auth_header(token1))
        ngoai_code = resp.get_json()["share_code"]
        resp = client.post("/api/v1/groups/join", headers=auth_header(token2),
                           data=json.dumps({"share_code": ngoai_code}))
        check("User2 joins Ngoại group", resp.status_code == 201)

        # User2 unsubscribes from Nội group
        resp = client.delete(f"/api/v1/groups/subscriptions/{noi_group['id']}", headers=auth_header(token2))
        check("Unsubscribe returns 200", resp.status_code == 200)

        # Verify user2 no longer has Nội subscription
        resp = client.get("/api/v1/groups", headers=auth_header(token2))
        data = resp.get_json()
        sub_names = [g["name"] for g in data["subscribed"]]
        check("User2 no longer subscribed to Nội", "Giỗ bên Nội" not in sub_names)
        check("User2 still subscribed to Ngoại", "Giỗ bên Ngoại" in sub_names)

        # ============================
        # 15. Revoke share
        # ============================
        print("\n--- 15. Revoke Share ---")
        resp = client.delete(f"/api/v1/groups/{ngoai_group['id']}/share", headers=auth_header(token1))
        check("DELETE share returns 200", resp.status_code == 200)

        # Verify user2 lost subscription
        resp = client.get("/api/v1/groups", headers=auth_header(token2))
        data = resp.get_json()
        check("User2 has 0 subscriptions after revoke", len(data["subscribed"]) == 0,
              f"got {len(data['subscribed'])}")

        # share_code no longer works
        resp = client.get(f"/api/v1/groups/preview?share_code={ngoai_code}")
        check("Revoked code returns 404", resp.status_code == 404)

        # ============================
        # 16. Delete non-default group
        # ============================
        print("\n--- 16. Delete Group ---")

        # Cannot delete default group
        resp = client.delete(f"/api/v1/groups/{default_group.id}", headers=auth_header(token1))
        check("Cannot delete default group (400)", resp.status_code == 400)

        # Delete Ngoại group → anniversaries move to default
        resp = client.delete(f"/api/v1/groups/{ngoai_group['id']}", headers=auth_header(token1))
        check("DELETE group returns 200", resp.status_code == 200)

        # Verify anniversary moved to default group
        resp = client.get("/api/v1/anniversaries", headers=auth_header(token1))
        all_anns = resp.get_json()
        ngoai_ann = [a for a in all_anns if a["person_name"] == "Ông Ngoại"]
        check("Ông Ngoại anniversary still exists", len(ngoai_ann) == 1)
        check("Moved to default group", ngoai_ann[0]["group_name"] == "Chung",
              f"got group_name='{ngoai_ann[0].get('group_name')}'")

        # Verify only 2 groups left
        resp = client.get("/api/v1/groups", headers=auth_header(token1))
        data = resp.get_json()
        check("User1 has 2 groups left", len(data["owned"]) == 2, f"got {len(data['owned'])}")

        # ============================
        # 17. Bulk create with group_id
        # ============================
        print("\n--- 17. Bulk Create with Group ---")
        resp = client.post("/api/v1/anniversaries/bulk", headers=auth_header(token1),
                           data=json.dumps({
                               "group_id": noi_group["id"],
                               "items": [
                                   {"person_name": "Cụ Nội 1", "lunar_day": 1, "lunar_month": 2},
                                   {"person_name": "Cụ Nội 2", "lunar_day": 15, "lunar_month": 6},
                               ]
                           }))
        check("Bulk create returns 201", resp.status_code == 201)
        bulk = resp.get_json()
        check("2 items created", len(bulk) == 2)
        check("Both in Nội group", all(a["group_id"] == noi_group["id"] for a in bulk))

        # ============================
        # 18. Move anniversary between groups
        # ============================
        print("\n--- 18. Move Anniversary ---")
        resp = client.put(f"/api/v1/anniversaries/{ann1['id']}", headers=auth_header(token1),
                          data=json.dumps({"group_id": noi_group["id"]}))
        check("Move returns 200", resp.status_code == 200)
        moved = resp.get_json()
        check("Anniversary moved to Nội group", moved["group_id"] == noi_group["id"])
        check("Group name updated", moved["group_name"] == "Giỗ bên Nội")

        # ============================
        # SUMMARY
        # ============================
        print("\n" + "=" * 60)
        total = passed + failed
        print(f"RESULTS: {passed}/{total} passed, {failed} failed")
        if failed == 0:
            print("ALL TESTS PASSED!")
        else:
            print(f"FAILURES: {failed} test(s) failed")
        print("=" * 60)

        return failed == 0


if __name__ == "__main__":
    success = test_groups()
    sys.exit(0 if success else 1)
