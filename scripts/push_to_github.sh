#!/usr/bin/env bash
set -Eeuo pipefail

# push_to_github.sh
# Usage:
#   VERBOSE=1 DRY_RUN=1 ./scripts/push_to_github.sh "push to github"
# Requires exact, case-sensitive token per project rules.

REQ_TOKEN="push to github"
VERBOSE="${VERBOSE:-0}"
DRY_RUN="${DRY_RUN:-0}"

TOKEN="${1-}"
if [[ "${TOKEN}" != "${REQ_TOKEN}" ]]; then
  echo "Authorization token missing or incorrect. Expected: '${REQ_TOKEN}'" >&2
  exit 2
fi

# Ensure we are inside a Git repo
if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo "Error: Not inside a Git repository." >&2
  exit 1
fi

# Ensure we're in the repo root
ROOT_DIR="$(git rev-parse --show-toplevel)"
cd "${ROOT_DIR}"

# Prepare cleanup trap for temp files
tmpHeader=""
trap 'rm -f ".commitmsg" "${tmpHeader}" "${tmpHeader}.new" 2>/dev/null || true' EXIT

# Determine branch and baseline range
BRANCH="$(git rev-parse --abbrev-ref HEAD)"
if git rev-parse --abbrev-ref --symbolic-full-name '@{upstream}' >/dev/null 2>&1; then
  RANGE='@{upstream}..HEAD'
else
  RANGE="origin/${BRANCH}..HEAD"
fi

# Safeguard: require explicit confirmation when pushing protected branches
if [[ "${BRANCH}" == "main" || "${BRANCH}" == "master" ]]; then
  if [[ "${CONFIRM_MAIN:-0}" != "1" ]]; then
    echo "Refusing to push to ${BRANCH} without CONFIRM_MAIN=1" >&2
    echo "Run: CONFIRM_MAIN=1 ./scripts/push_to_github.sh \"push to github\"" >&2
    exit 3
  fi
fi

# Gather changed files since baseline and include working tree changes
range_files="$(git diff --name-only "${RANGE}" || true)"
wt_files="$(git diff --name-only HEAD || true)"
FILES="$(printf "%s\n%s\n" "${range_files}" "${wt_files}" | sed '/^$/d' | sort -u)"

# Abort if nothing to push (no ahead commits and clean tree)
ahead_count="$(git rev-list --count "${RANGE}" 2>/dev/null || echo 0)"
if [[ "${ahead_count}" -eq 0 && -z "$(git status --porcelain)" ]]; then
  echo "Nothing to push (no local commits ahead, clean working tree)."
  exit 0
fi

# Warn about untracked files (they will be included by -A)
if [[ -n "$(git ls-files --others --exclude-standard)" ]]; then
  echo "Warning: Untracked files present and will be included by commit."
fi

# Build a one-line, high-level summary
lowerExts="$(printf "%s\n" "${FILES}" | sed -n 's/.*\.\([^.\/]*\)$/\1/p' | tr '[:upper:]' '[:lower:]' | sort -u | tr '\n' ' ')"
contains() { grep -q -w "$1" <<< "${lowerExts}"; }

parts=""
contains php  && parts="PHP"
contains js   && parts="${parts:+${parts}/}JS"
contains css  && parts="${parts:+${parts}/}CSS"
contains md   && parts="${parts:+${parts}/}docs"
contains json && parts="${parts:+${parts}/}data"

scope="$(printf "%s\n" "${FILES}" | awk -F/ 'NF>1{print $1} NF==1{print "."}' | sort -u | head -n 3 | tr '\n' '/' | sed -E 's#/$##')"

if [[ -z "${parts}" && -z "${scope}" ]]; then
  SUMMARY="update project files"
else
  SUMMARY="update ${parts:-project files}${scope:+ in ${scope}}"
fi

# Determine environment label based on deploy-config.json target_folder
envLabel=""
if [[ -f "deploy-config.json" ]]; then
  if command -v jq >/dev/null 2>&1; then
    tf=$(jq -r '.target_folder // empty' deploy-config.json 2>/dev/null || true)
  else
    tf=$(sed -n 's/.*"target_folder"[[:space:]]*:[[:space:]]*"\([^"\n]*\)".*/\1/p' deploy-config.json | head -n 1)
  fi
  if [[ "$tf" == "otter" ]]; then
    envLabel="LIVE "
  elif [[ "$tf" == "otter3" ]]; then
    envLabel="TEST "
  fi
fi

# Prepend environment label to summary when available
if [[ -n "${envLabel}" ]]; then
  SUMMARY="${envLabel}${SUMMARY}"
fi

TS="$(date +"%Y-%m-%d %H:%M:%S")"
HEADER="## push to github — ${TS}"

if [[ "${VERBOSE}" == "1" ]]; then
  echo "Branch: ${BRANCH}"
  echo "Range:  ${RANGE}"
  echo "Files:"
  if [[ -n "${FILES}" ]]; then printf "  %s\n" ${FILES}; else echo "  <none>"; fi
  echo "Summary: ${SUMMARY}"
fi

if [[ "${DRY_RUN}" == "1" ]]; then
  echo "DRY RUN: Would prepend changelog, commit with summary, and push."
  exit 0
fi

# Prepend changelog entry
tmpHeader="$(mktemp)"
printf "%s\n\n- %s\n\n" "${HEADER}" "${SUMMARY}" > "${tmpHeader}"
if [[ -f "changelog.md" ]]; then
  cat "${tmpHeader}" "changelog.md" > "${tmpHeader}.new" && mv "${tmpHeader}.new" "changelog.md"
else
  mv "${tmpHeader}" "changelog.md"
  tmpHeader=""  # already moved
fi

# Roll-up commit and push
printf "%s\n" "${SUMMARY}" > ".commitmsg"
git add -A
git commit -F ".commitmsg"
git push

echo "Pushed '${BRANCH}' with summary: ${SUMMARY}"


