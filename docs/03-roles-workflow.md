# 03 — Roles & Editorial Workflow (modelled on OJS 3.3)

## Roles
| Role | Level | Can do |
|---|---|---|
| Site Administrator | site | Everything; site settings, create journals, system info |
| Journal Manager | journal | All journal settings, users & roles, issues, all submissions |
| Editor | journal | All submissions: assign participants, record decisions, publish |
| Section Editor | journal | Only submissions they are assigned to (optionally limited to their sections) |
| Reviewer | journal | Only review assignments sent to them |
| Author | journal | Their own submissions |
| Copyeditor / Layout Editor / Proofreader | journal | Only their assigned stage of assigned submissions (P1; can merge into Editor for MVP) |
| Reader | journal | Public site + profile |

A user can hold several roles. The dashboard shows the union of what their roles allow.
A "role switcher" isn't needed; the tabs and lists are filtered by permission.

## Submission lifecycle

Stages: **1 Submission → 2 Review → 3 Copyediting → 4 Production → Published**
(plus terminal states Declined and Withdrawn/Deleted).

```
[Author finishes wizard]
        │  status=queued, stage=submission
        ▼
  SUBMISSION stage ── Editor: "Decline Submission" ──► DECLINED (archived)
        │ "Send to Review"      │ "Accept and Skip Review" ──┐
        ▼                       ▼                            │
   REVIEW stage (Round 1..n)                                 │
    - Editor adds reviewers (deadline for response + review) │
    - Reviewers accept/decline → submit review + recommendation
    - Editor decisions:                                      │
        "Request Revisions"  → author uploads revised files  │
              (choose: revisions will NOT be subject to new review round /
                       resubmit for review → new round)      │
        "Accept Submission"  ────────────────────────────────┤
        "Decline Submission" → DECLINED                      │
        ▼                                                    ▼
  COPYEDITING stage: participants exchange files via discussions;
                     Editor marks copyedited files → "Send To Production"
        ▼
  PRODUCTION stage: layout editor/editor uploads galleys (PDF);
                    Editor fills publication metadata → "Schedule For Publication"
                    (choose issue) → published when the issue is published,
                    or immediately if the issue is already live
        ▼
  PUBLISHED (status=published). "Unpublish" and "Create New Version" are P2.
```

### Statuses
`incomplete` (wizard not finished) · `queued` (active) · `scheduled` · `published` ·
`declined`. `stage_id` = 1..4.

### Review assignment states
`awaiting_response` → `accepted` | `declined` → `review_submitted` → `thanked`
(Editor clicks "Thank Reviewer"). Also flag `overdue` when past a due date, and allow
`cancelled` (Editor "Unassign").

Reviewer recommendations: Accept Submission · Revisions Required · Resubmit for Review ·
Resubmit Elsewhere · Decline Submission · See Comments.

Review method per assignment: **Anonymous Reviewer / Disclosed Author** (single-blind,
the journal default) · Anonymous Reviewer/Anonymous Author (double-blind) · Open.
When anonymous, hide reviewer identity from the author everywhere. For double-blind, also
hide author names from the reviewer.

## Author submission wizard (5 steps, as in OJS 3.3)
1. **Start**: section dropdown, language, submission requirements checklist (every box
   must be ticked), comments for the editor, ☐ privacy consent, copyright notice ☐ agree.
2. **Upload Submission**: upload files with a component type (Article Text, Research
   Instrument, Research Materials, Research Results, Transcripts, Data Analysis, Data Set,
   Source Texts, Other). Rename/delete before continuing. At least 1 Article Text required.
3. **Enter Metadata**: prefix, title*, subtitle, abstract* (rich text, optional word
   limit), contributors list (add/edit/order; given/family name, email, country,
   affiliation, ORCID, bio, role Author/Translator, "principal contact" radio, "include in
   browse lists" ☐), keywords (tags), references (textarea, one per line).
4. **Confirmation**: summary + "Finish Submission" → confirm modal.
5. **Next Steps**: "Submission complete" + links (review this submission, create a new
   submission, return to dashboard).
The wizard saves after every step (status=incomplete) so authors can resume.

## Editor actions per stage (right-hand action panel on the workflow page)
- **Submission**: Send to Review · Accept and Skip Review · Decline Submission;
  Participants panel: Assign (pick role + user + notify message).
- **Review**: Add Reviewer (search reviewers: name, interests, active reviews count,
  last completed, avg days) · Request Revisions · Accept Submission · Decline Submission ·
  New Review Round. Reviewer list shows status chips + menu (Review Details, Email
  Reviewer, Edit deadlines, Unassign, Reinstate, History, Thank Reviewer).
- **Copyediting**: Send To Production.
- **Production**: Schedule For Publication (opens the Publication tab).

Each decision opens a modal with a pre-filled email to the author (editable,
"don't send email" option) and, where relevant, file selection (e.g. which files go to
the next stage).

## Notifications / emails (templates editable in settings)
| Key | Trigger | To |
|---|---|---|
| SUBMISSION_ACK | Submission finished | Author |
| SUBMISSION_NOTIFY_EDITORS | New submission | Editors/managers |
| EDITOR_ASSIGN | Editor assigned | Assigned editor |
| REVIEW_REQUEST | Reviewer invited | Reviewer (accept/decline links) |
| REVIEW_CONFIRM / REVIEW_DECLINE | Reviewer responds | Editor |
| REVIEW_REMIND | Manual reminder | Reviewer |
| REVIEW_ACK | Thank reviewer | Reviewer |
| REVIEW_COMPLETE | Review submitted | Editor |
| EDITOR_DECISION_ACCEPT / _DECLINE / _REVISIONS / _RESUBMIT / _SEND_TO_EXTERNAL / _SEND_TO_PRODUCTION | Decision | Author |
| REVISED_VERSION_NOTIFY | Author uploads revision | Editor |
| DISCUSSION_NOTIFICATION | New discussion message | Thread participants |
| PUBLICATION_NOTIFY | Article published | Author |
| PASSWORD_RESET_CONFIRM / USER_REGISTER | Account | User |
| ANNOUNCEMENT | New announcement | Users who opted in (P2) |

Variables use `{$authorName}`, `{$submissionTitle}`, `{$submissionUrl}`,
`{$reviewDueDate}`, `{$journalName}`, `{$editorialContactSignature}`, etc.
On shared hosting, send synchronously via SMTP. If SMTP isn't configured, log emails
to the DB table `email_log` and show them in Admin → "Outbox" so the demo still works.

Every user also gets **in-app notifications** (bell icon in the back-end header) for the
same events.

## Discussions
Each stage has discussion threads (subject, participants picked from the stage
participants, messages with attachments). Unread counts show in the stage tab.

## Submission activity log
Record every action (who, what, when) in `event_log`; shown under the "Activity Log" button.
