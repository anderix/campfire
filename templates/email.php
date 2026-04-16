<?php
/**
 * Newsletter email template.
 *
 * Variables available:
 *   $troopName    - string
 *   $memberName   - string (recipient display name)
 *   $events       - array of parsed calendar events
 *   $accounts     - array of scout account rows for this family
 *   $unsubscribeUrl - string
 */
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background:#f5f5f5; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5; padding:20px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; max-width:600px; width:100%;">

    <!-- Header -->
    <tr>
        <td style="background:#1a1a1a; padding:24px 32px;">
            <h1 style="margin:0; color:#ffffff; font-size:20px; font-weight:600;"><?= htmlspecialchars($troopName) ?></h1>
        </td>
    </tr>

    <!-- Greeting -->
    <tr>
        <td style="padding:28px 32px 8px;">
            <p style="margin:0; font-size:15px; color:#1a1a1a;">Hi <?= htmlspecialchars($memberName) ?>,</p>
        </td>
    </tr>

    <!-- Events -->
    <?php if (!empty($events)): ?>
    <tr>
        <td style="padding:20px 32px 8px;">
            <h2 style="margin:0 0 12px; font-size:16px; color:#1a1a1a; border-bottom:2px solid #e0e0e0; padding-bottom:8px;">Upcoming Events</h2>
            <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px; color:#1a1a1a;">
                <?php foreach ($events as $event): ?>
                <tr>
                    <td style="padding:8px 0; border-bottom:1px solid #f0f0f0; vertical-align:top; width:160px;">
                        <strong><?= htmlspecialchars(formatEventDateRange($event)) ?></strong>
                        <?php $timeLabel = formatEventTimeRange($event); ?>
                        <?php if ($timeLabel !== ''): ?>
                            <br><span style="color:#666;"><?= htmlspecialchars($timeLabel) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:8px 0 8px 12px; border-bottom:1px solid #f0f0f0; vertical-align:top;">
                        <strong>
                            <?php if (!empty($event['url'])): ?>
                                <a href="<?= htmlspecialchars($event['url']) ?>" style="color:#1a1a1a; text-decoration:none;"><?= htmlspecialchars($event['summary']) ?></a>
                            <?php else: ?>
                                <?= htmlspecialchars($event['summary']) ?>
                            <?php endif; ?>
                        </strong>
                        <?php if ($event['location']): ?>
                            <br><span style="color:#666;"><?= htmlspecialchars($event['location']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </td>
    </tr>
    <?php endif; ?>

    <!-- Scout Accounts -->
    <?php if (!empty($accounts)): ?>
    <tr>
        <td style="padding:20px 32px 8px;">
            <h2 style="margin:0 0 12px; font-size:16px; color:#1a1a1a; border-bottom:2px solid #e0e0e0; padding-bottom:8px;">Scout Account Balances</h2>
            <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px; color:#1a1a1a;">
                <?php foreach ($accounts as $account): ?>
                <tr>
                    <td style="padding:6px 0;"><?= htmlspecialchars($account['label']) ?></td>
                    <td style="padding:6px 0; text-align:right; font-weight:600; font-variant-numeric:tabular-nums;">
                        <?php
                        $bal = (float) $account['balance'];
                        $color = $bal > 0 ? '#2e7d32' : ($bal < 0 ? '#c62828' : '#666666');
                        ?>
                        <span style="color:<?= $color ?>;">$<?= number_format($bal, 2) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </td>
    </tr>
    <?php endif; ?>

    <!-- Footer -->
    <tr>
        <td style="padding:28px 32px; border-top:1px solid #e0e0e0; margin-top:16px;">
            <p style="margin:0; font-size:12px; color:#999999;">
                This email was sent by <?= htmlspecialchars($troopName) ?> using <a href="https://github.com/anderix/campfire" style="color:#999;">Campfire</a>.
                <br><a href="<?= htmlspecialchars($unsubscribeUrl) ?>" style="color:#999999;">Unsubscribe</a>
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
