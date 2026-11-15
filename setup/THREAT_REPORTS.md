# Threat reports and PDF evidence

Install PHP dependencies with `composer install` and apply migrations with
`php artisan migrate`. The PDF uses Dompdf locally; no external chart service
or browser printing is required.

Open **Threat Reports**, select a time window, optionally enter an exact PPPoE
name (or source IP when no subscriber name exists), and choose **Export PDF**.
Expand **All flagged clients** for an individual client PDF. CSV exports use the
same filters and include review metadata and all matching events.

The PDF includes client detection charts, reviewed outcomes, complete client
summaries, and the latest 100 matching client events. This detail limit is
disclosed in the report; charts and summary counts use the full filtered data.
Blocked and resolved history is retained in reports, so totals can be higher
than older reports that excluded logs with a status.

Administrators can expand **Review event** for either attack or benign
predictions, select the actual activity, and record supporting evidence such
as packet capture findings. The review stores the reviewer ID and timestamp
without changing the model prediction. Viewers can download reports but cannot
submit reviews. A later review replaces the event's current assessment.

- Predicted attack + reviewed attack = true positive.
- Predicted attack + reviewed benign = false positive.
- Predicted benign + reviewed attack = false negative.
- Predicted benign + reviewed benign = true negative.

Events without a review remain unreviewed. Prediction confidence, a block,
or a resolved status alone does not establish actual activity. These charts
describe recorded, reviewed events; they do not measure attacks absent from
the logs or independently establish model accuracy.

Run `php artisan test --filter=ThreatReportTest` for PDF generation, filtering,
outcome counts, evidence validation, and access permission coverage.
