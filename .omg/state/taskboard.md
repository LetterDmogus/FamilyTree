# Taskboard

## Stage: Done

### Admin Panel Implementation (Current Goal)

| Task ID | Priority | Status | Owner | Dependency | Worktree | Lane Health | Summary | Evidence |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| A1 | p0 | verified | omg-executor | - | root | clean | Soft Delete Migration & Models | Migration run, `deleted_at` added to 4 tables. |
| A2 | p0 | verified | omg-executor | A1 | root | clean | Custom Role Model & SoftDeletes | `app/Models/Role.php` created and config updated. |
| A3 | p1 | verified | omg-executor | - | root | clean | Generic Vue CRUD Template | `CrudTable.vue` built with search, pagination, soft-delete. |
| A4 | p1 | verified | omg-executor | A3 | root | clean | Admin Controllers (Query Builder) | 4 controllers created using `spatie/laravel-query-builder`. |
| A5 | p1 | verified | omg-executor | A4 | root | clean | Admin Routes (can middleware) | New routes grouped under `/admin`. |
| A6 | p2 | verified | omg-executor | A3, A5 | root | clean | Admin Vue Pages (4 pages) | Users, Roles, Social Media, Data Details pages implemented. |
| A7 | p1 | verified | omg-executor | A5 | root | clean | Role-based Sidebar Update | Sidebar updated with "Admin Panel" section. |
| A8 | p2 | verified | omg-executor | A6 | root | clean | Clean up old files | Legacy Role management files removed. |

---

### Previous Goals (Archived)

| Task ID | Priority | Task | Owner | Dependency | Status |
| --- | --- | --- | --- | --- | --- |
| R1-R10 | - | Role & Permission System Initial | omg-executor | - | Done |
| T1-T10 | - | Family Tree Core Implementation | omg-executor | - | Done |

## Acceptance Criteria
- AC1: Unified CRUD template used for all 4 admin pages.
- AC2: Search, Pagination, and Soft Delete (Active/Trash/All) work.
- AC3: "Hard Delete" only for superadmin role.
- AC4: Professional table layout with action dropdowns.
- AC5: Navigation strictly role-based using Wayfinder.
