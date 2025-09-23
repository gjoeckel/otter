Core datasets (server returns per request)
  - registrations_submissions
  - registrations_cohort
  - submissions_enrollments_tou
  - submissions_enrollments_registrations
  - cohort_enrollments_tou
  - cohort_enrollments_registrations

Active dataset → report filters
  - registrations_submissions: registrants.php mode=date; enrollees.php mode=date (plus enrollment_mode=by-tou|by-registration)
  - registrations_cohort: registrants.php mode=cohort; enrollees.php mode=cohort (plus enrollment_mode=by-tou|by-registration)
  - submissions_enrollments_tou: enrollees.php enrollment_mode=by-tou; mode derives from active registrations dataset (date|cohort)
  - submissions_enrollments_registrations: enrollees.php enrollment_mode=by-registration; mode derives from active registrations dataset
  - cohort_enrollments_tou: enrollees.php enrollment_mode=by-tou; mode=cohort
  - cohort_enrollments_registrations: enrollees.php enrollment_mode=by-registration; mode=cohort

- Trigger rules
  - Registrations radio change: selects registrations_by_date vs registrations_by_cohort; set both report links’ mode accordingly. Enrollments dataset unchanged.
  - Enrollments radio change: switches between enrollments_by_tou vs enrollments_by_registration; only updates enrollees.php enrollment_mode. Registrations dataset and both links’ mode stay locked.
  - Apply date range:
    - ALL range: force registrations_by_date; disable cohort; set both links mode=date.
    - Other ranges: honor current Registrations radio; compute all six datasets server-side; pick the two active ones based on radios.

- Minimal frontend hookup
  - After API returns all four arrays, store them and set:
    - activeRegistrationsDataset = registrations_submissions|registrations_cohort
    - activeEnrollmentsDataset = submissions_enrollments_(tou|registrations) when activeRegistrationsDataset is date; or cohort_enrollments_(tou|registrations) when activeRegistrationsDataset is cohort
  - When rendering: tables read from the active datasets; report links read:
    - registrants.php?…&mode=(activeRegistrationsDataset == registrations_cohort ? 'cohort' : 'date')
    - enrollees.php?…&enrollment_mode=(by-tou|by-registration)&mode=(same mode as activeRegistrationsDataset)

- ALL safeguard
  - When ALL, expose only registrations_by_date; keep Enrollments toggle working; always generate mode=date for both links.

Locking behavior
  - Registrations are the authority: switching the Registrations radio locks the current mode for Reports filtering and column values.
  - Enrollment toggles update only Enrollments counts/links; Registrations remain unchanged until Registrations radio changes again.

Backend filtering rules
  - registrations_submissions: filter submissions by submission date range.
  - registrations_cohort: filter submissions by cohort/year derived from the date range; for ALL, return all submissions.
  - submissions_enrollments_*: filter enrollments by chosen enrollment date (TOU or registration date) within range.
  - cohort_enrollments_*: same as above, but further restricted to enrollments whose linked registrant falls within the cohort-year range (requires stable join key).