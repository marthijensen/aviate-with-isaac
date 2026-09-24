# Git Workflow

This document explains how the Aviate with Isaac development team should use GitHub during development.

## 1. Main Branch

The `main` branch should contain stable project code.

Do not develop directly on `main`.

Each user story should be completed in its own branch and reviewed before being merged into `main`.

## 2. Before Starting a Story

Before starting work on a Trello story, make sure your local project is up to date.

Run:

```bash
git checkout main
git pull origin main
```

This switches you to the `main` branch and downloads the latest changes from GitHub.

## 3. Create a Branch

Each user story should be developed in its own branch.

After updating `main`, create a new branch for the story you are working on.

Example:

```bash
git checkout -b feature/2-1-add-student
```

This creates the new branch and switches you to it.

Do not begin coding until you are on your feature branch.

## 4. Branch Naming

Use one branch per user story.

For features, use this format:

```text
feature/story-number-short-description
```

Examples:

```text
feature/2-1-add-student
feature/2-2-view-student-profile
feature/3-1-schedule-lesson
feature/6-1-create-invoice
```

For bug fixes, use:

```text
bugfix/short-description
```

Example:

```text
bugfix/student-form-validation
```

Keep branch names short, clear, and related to the Trello story.

## 5. Work on the Story

Complete the assigned Trello story on your feature branch.

The Trello card should include:

- User Story
- Definition of Done
- Development Checklist

Before submitting the story for review:

- Complete the development checklist
- Test the feature
- Confirm the Definition of Done is satisfied
- Make sure the feature does not break existing functionality

Avoid making unrelated changes on the same branch.

## 6. Save Your Changes

Check which files have changed:

```bash
git status
```

Add your changes:

```bash
git add .
```

Commit your changes:

```bash
git commit -m "Add student creation form"
```

Commit messages should be short and explain what changed.

Examples:

```text
Add student creation form
Connect student form to Supabase
Add scheduling calendar
Fix student validation
Create invoice page
```

You can create multiple commits while working on one story.

## 7. Push Your Branch to GitHub

When you are ready to save your branch to GitHub, run:

```bash
git push origin feature/2-1-add-student
```

Replace the branch name with the branch you are actually working on.

Example:

```bash
git push origin feature/3-1-schedule-lesson
```

## 8. Create a Pull Request

When the story is complete and tested:

1. Open the GitHub repository.
2. Go to **Pull requests**.
3. Click **New pull request**.
4. Set the base branch to `main`.
5. Select your feature branch as the compare branch.
6. Add a clear title.
7. Reference the related Trello story.
8. Briefly explain what was changed.
9. Confirm the Definition of Done was met.
10. Request a teammate to review the Pull Request.

Example Pull Request title:

```text
Story 2.1 - Add Student
```

Example description:

```text
Completed Story 2.1 - Add Student

Changes:
- Added student creation form
- Added required field validation
- Connected student form to Supabase
- Added success and error messages
- Confirmed new students appear in the student list

Definition of Done:
- Completed and tested
```

## 9. Code Review

A teammate should review the Pull Request before it is merged.

The reviewer should check:

- The feature works as expected
- The Trello Definition of Done is satisfied
- The development checklist is complete
- Existing features still work
- No passwords, API keys, or `.env` files were committed
- Code is organized and understandable
- Database changes are documented when applicable

If changes are needed, the reviewer should leave comments on the Pull Request.

The developer should make the requested changes on the same feature branch and push them again.

The Pull Request will update automatically.

## 10. Merge the Pull Request

Once the Pull Request has been reviewed and approved, it can be merged into `main`.

After merging:

1. Delete the feature branch if it is no longer needed.
2. Move the related Trello story to **Done**.
3. Pull the newest version of `main` before starting another story.

## 11. Starting the Next Story

Before starting another story, return to `main`:

```bash
git checkout main
```

Pull the latest changes:

```bash
git pull origin main
```

Then create a new branch for the next story.

Example:

```bash
git checkout -b feature/3-1-schedule-lesson
```

Repeat the same workflow for each story.

## 12. Database and Supabase Changes

Supabase database changes should be coordinated with the team.

Before making a major database change:

- Discuss the change with the team
- Make sure the change supports an assigned story
- Avoid deleting or renaming shared tables without approval
- Document major database changes
- Test the change before merging related code

Do not place Supabase secret keys, passwords, or other sensitive information in GitHub.

## 13. Environment Files

The real `.env` file should remain local and must never be committed.

The repository should only contain:

```text
.env.example
```

The real local `.env` file may contain:

```env
SUPABASE_URL=
SUPABASE_PUBLISHABLE_KEY=
SUPABASE_SECRET_KEY=
```

Never commit:

```text
.env
```

## 14. Important Team Rules

- Do not develop directly on `main`
- Use one branch per user story
- Pull the latest `main` before starting new work
- Test your work before opening a Pull Request
- Use Pull Requests before merging code
- Never commit `.env`
- Never commit passwords or Supabase secret keys
- Coordinate major Supabase or database changes with the team
- Keep Trello updated throughout the sprint
- Complete the development checklist before requesting review
- A story should only move to **Done** when its Definition of Done is satisfied

## 15. Workflow Summary

```text
Trello Story
     ↓
Pull Latest main
     ↓
Create Feature Branch
     ↓
Develop the Story
     ↓
Test the Feature
     ↓
Commit Changes
     ↓
Push Branch to GitHub
     ↓
Open Pull Request
     ↓
Team Review
     ↓
Merge into main
     ↓
Move Trello Story to Done
```
