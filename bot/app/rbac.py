
def can_manage(role: str) -> bool:
    return role in {"admin", "manager"}
