"""Verify saved observations from real browser, mail and WordPress runs."""
import json
from pathlib import Path

base = Path(__file__).parent / "evidence"
checks = 0


def load(name):
    return json.loads((base / name).read_text())


def check(value, message):
    global checks
    if not value:
        raise AssertionError(message)
    checks += 1


def row(case, order):
    page = load(case + ".json")
    check(page["status"] == 200 and not page["criticalError"], case + " must render")
    check(not any("cancel" in action["classes"].split() for item in page["rows"] for action in item["actions"]), case + " must not introduce Cancel")
    return next(item for item in page["rows"] if item["order"] == "#" + str(order))


for case, order, visible in [
    ("available-existing", 23966, True), ("available-absent", 23975, False),
    ("always-absent", 23975, True), ("custom-pending", 23975, True),
    ("custom-completed-denied", 23966, False), ("custom-completed-paid", 23979, True),
    ("never-existing", 23966, False), ("disabled-existing", 23966, False),
    ("core-only", 23966, True), ("pdf-inactive", 23966, False),
    ("provider-failure", 23966, False),
]:
    item = row(case, order)
    check(any("invoice" in action["classes"].split() for action in item["actions"]) == visible, case + " invoice availability")
    check(any(action["name"] == "View" for action in item["actions"]), case + " keeps View")

for case, expected in [("download-mode", ""), ("display-mode", "_blank")]:
    invoice = next(a for a in row(case, 23966)["actions"] if "invoice" in a["classes"].split())
    check(invoice["target"] == expected, case + " follows provider target")
check(any(a["name"] == "QA <Invoice> & bill" for a in row("custom-label", 23966)["actions"]), "Provider label survives as literal text")
settling = row("settling-renewal", 23984)
check("Payment in progress" in settling["text"], "Settlement explanation remains")
check(not any(a["name"] == "Pay" for a in settling["actions"]), "Settling renewal cannot be paid again")
check(any(a["name"] == "Invoice" for a in settling["actions"]), "Settlement does not hide invoice")
check(any(a["path"].startswith("/wcpdf/invoice/") for a in row("pretty-links", 23966)["actions"]), "Pretty invoice route used")

before = load("pre-list-state.json")["states"]["absent"]
after = load("post-list-state.json")["states"]["absent"]
check(before["invoice_exists"] is False and after["invoice_exists"] is False, "Lists do not create invoices")
first = load("after-first-download-state.json")["states"]["absent"]
second = load("after-second-download-state.json")["states"]["absent"]
check(first["invoice_exists"] and first["invoice_number"], "Actual download creates invoice")
check(first["invoice_number"] == second["invoice_number"], "Repeat download preserves invoice identity")
for name in ["portal-invoice-download.pdf", "pretty-invoice-download.pdf", "new-invoice-download.pdf", "repeated-invoice-download.pdf", "renewal-email-ordering-after-active-invoice-24022.pdf"]:
    check((base / name).read_bytes().startswith(b"%PDF-"), name + " is a PDF")

for case in ["guest-invoice", "foreign-invoice"]:
    response = load(case + ".json")
    check(response["contentType"] != "application/pdf" and "permissions" in response["text"], case + " denies document")
check("Login" in (base / "guest-subscription.txt").read_text(), "Guest sees login")
check("do not have permission" in (base / "foreign-subscription.txt").read_text(), "Other customer cannot view subscription")

check(load("email-send-before.json")["attachments"] == [], "Original core email defect recorded")
check(load("email-send-after.json")["attachments"][0]["header"].startswith("%PDF-"), "Core email attaches PDF")
before = load("renewal-email-ordering-before-active.json")
after = load("renewal-email-ordering-after-active.json")
check(before["invoice_order_total_at_send"] == 25 and before["final_order_total"] == 20, "Original amount mismatch reproduced")
check(after["invoice_order_total_at_send"] == after["final_order_total"] == 20, "Fixed email uses final credited amount")
check(after["customer_credit_after"] == after["subscription_credit_after"] == 0, "Fixture balances restored to zero")
messages = load("mail-final-messages.json")["messages"]
check(any("Store credit used for Order #24022" in m["Subject"] and m["Attachments"] == 1 for m in messages), "Actual Pro email contains attachment")
check(not any("QA DUPLICATE" in m["Subject"] for m in load("mail-duplicate-check.json")["messages"]), "Repeated event sends no duplicate invoice")
check(load("vat-fixed-runtime.json")["vat"] == "QA-VAT-20261029", "New renewal retains VAT identifier")
check("QA Customer VAT" in (base / "vat-parent-text.txt").read_text(), "Original admin identification visible")
check("QA Customer VAT" not in (base / "vat-renewal-before-text.txt").read_text(), "Original loss visible in admin")
check("QA Customer VAT" in (base / "vat-renewal-after-text.txt").read_text(), "Fixed identification visible in admin")

restored = load("restoration-verification.json")
for key, value in restored.items():
    check(value == 0 if key.endswith("balance") else value is True, "Restored " + key)
print(f"PASS: {checks} assertions against saved real browser/mail/runtime evidence")
