<?php
/**
 * @var string $subject
 * @var string $heading
 * @var string $text
 * @var array $details
 * @var array $settings
 * @var string|null $link
 * @var string|null $link_label
 */
$color = !empty($settings['company_color']) ? $settings['company_color'] : '#3fa9c9';
?>
<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title><?= e($subject) ?></title>
</head>
<body style="background:#f6f6f6;font-family:sans-serif;font-size:14px;margin:0;padding:24px;">
<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:6px;overflow:hidden;">
    <div style="background:<?= e($color) ?>;color:#fff;padding:16px 24px;font-size:18px;">
        <?= e($settings['company_name'] ?? 'Easy!Appointments') ?>
    </div>
    <div style="padding:24px;">
        <h2 style="margin-top:0;"><?= e($heading) ?></h2>
        <p><?= e($text) ?></p>
        <table style="width:100%;border-collapse:collapse;margin:16px 0;">
            <?php foreach ($details as $label => $value): ?>
                <tr>
                    <td style="padding:6px 0;color:#777;width:35%;"><?= e($label) ?></td>
                    <td style="padding:6px 0;"><?= e($value) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (!empty($link)): ?>
            <p>
                <a href="<?= e($link) ?>"
                   style="background:<?= e($color) ?>;color:#fff;text-decoration:none;padding:10px 18px;border-radius:4px;display:inline-block;">
                    <?= e($link_label ?: 'Open') ?>
                </a>
            </p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
