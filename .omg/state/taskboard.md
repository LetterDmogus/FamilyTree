# Taskboard

## Stage: team-verify (In Progress)

| Task ID | Priority | Task | Owner | Dependency | Path Type | Worktree | Lane Notes | Validation | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| T1 | p0 | Create migrations: `relations`, `user_profiles`, `master_social_medias` | omg-executor | - | critical | isolated | - | Migrate success | Done |
| T2 | p0 | Implement Models & `User` relationships | omg-executor | T1 | critical | sequential | - | Models test pass | Done |
| T3 | p1 | Build `FamilyTreeService` (Recursive) & `RelationshipCalculator` (BFS) | omg-executor | T2 | critical | sequential | - | Path labels accurate | Done |
| T4 | p1 | Create `FamilyTreeController` & Routes | omg-executor | T3 | critical | sequential | - | Routes reachable | Done |
| T5 | p1 | Develop `TreeNode.vue` (Recursive component) | omg-executor | T4 | critical | sidecar | - | Renders nested levels | Done |
| T6 | p1 | Develop `NodePanel.vue` (Detail display & Action buttons) | omg-executor | T4 | critical | sidecar | - | Displays selected node | Done |
| T7 | p1 | Develop `AddRelationModal.vue` (Search/Form dual mode) | omg-executor | T4 | critical | sidecar | - | Search results valid | Done |
| T8 | p1 | Assemble `FamilyTree/Show.vue` (Main Layout) | omg-executor | T5, T6, T7 | critical | sequential | - | Full interactive loop | Done |
| T9 | p2 | Add Seeders for Master Data & Sample Family | omg-executor | T2 | sidecar | - | - | `db:seed` success | Done |
| T10 | p2 | Implement Validation & Pest Tests | omg-executor | T3, T4 | sidecar | - | - | All tests pass | Done |

## Acceptance Criteria (from TEST.md - Test 1)
- AC1: Each element in diagram is 1 user, spouses separated.
- AC2: View 3 levels (up to grandparents, down to grandchildren).
- AC3: Only add child or spouse.
- AC4: No level display.
- AC5: Logged-in user is "saya", labels from FT.md.
