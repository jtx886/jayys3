<?php
class Email {
    public static function send($to, $subject, $body, $isHtml = true) {
        require_once __DIR__ . '/PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/SMTP.php';
        require_once __DIR__ . '/PHPMailer/Exception.php';
        
        // 如果PHPMailer不存在，使用内置mail函数作为备选
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return self::sendNative($to, $subject, $body, $isHtml);
        }

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML($isHtml);

            $mail->Subject = $subject;
            $mail->Body = $body;
            if (!$isHtml) {
                $mail->AltBody = strip_tags($body);
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("邮件发送失败: " . $e->getMessage());
            return self::sendNative($to, $subject, $body, $isHtml);
        }
    }

    private static function sendNative($to, $subject, $body, $isHtml = true) {
        $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
        $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
        if ($isHtml) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }
        $headers .= "X-Mailer: PHP/" . phpversion();

        return @mail($to, $subject, $body, $headers);
    }

    public static function generateCode($length = 6) {
        return str_pad(random_int(0, 999999), $length, '0', STR_PAD_LEFT);
    }

    public static function saveCode($email, $code, $purpose = 'register') {
        $db = Database::getInstance();
        $expires = date('Y-m-d H:i:s', time() + 300);
        $db->insert('email_codes', [
            'email' => $email,
            'code' => $code,
            'purpose' => $purpose,
            'expires_at' => $expires
        ]);
    }

    public static function verifyCode($email, $code, $purpose = 'register') {
        $db = Database::getInstance();
        $record = $db->fetchOne(
            "SELECT * FROM email_codes WHERE email = ? AND code = ? AND purpose = ? AND used = 0 AND expires_at > ? ORDER BY id DESC LIMIT 1",
            [$email, $code, $purpose, date('Y-m-d H:i:s')]
        );

        if ($record) {
            $db->update('email_codes', ['used' => 1], 'id = ?', [$record['id']]);
            return true;
        }
        return false;
    }

    public static function getVerifyEmailHtml($code, $username = '') {
        return '
        <!DOCTYPE html>
        <html lang="zh-CN">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . SITE_NAME . ' - 邮箱验证</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    padding: 40px 20px;
                }
                .container {
                    max-width: 500px;
                    margin: 0 auto;
                    background: #fff;
                    border-radius: 20px;
                    overflow: hidden;
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                }
                .header {
                    background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
                    padding: 40px 30px;
                    text-align: center;
                    color: #fff;
                }
                .logo {
                    width: 70px;
                    height: 70px;
                    background: rgba(255,255,255,0.2);
                    border-radius: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 15px;
                    font-size: 28px;
                    font-weight: bold;
                    backdrop-filter: blur(10px);
                }
                .header h1 {
                    font-size: 24px;
                    font-weight: 600;
                    margin-bottom: 8px;
                }
                .header p {
                    opacity: 0.9;
                    font-size: 14px;
                }
                .content {
                    padding: 40px 30px;
                }
                .greeting {
                    color: #1f2937;
                    font-size: 16px;
                    line-height: 1.6;
                    margin-bottom: 25px;
                }
                .greeting strong {
                    color: #7c3aed;
                }
                .code-box {
                    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
                    border-radius: 16px;
                    padding: 30px 20px;
                    text-align: center;
                    margin-bottom: 25px;
                    border: 2px dashed #7c3aed;
                }
                .code-title {
                    color: #6b7280;
                    font-size: 14px;
                    margin-bottom: 15px;
                }
                .code {
                    font-size: 42px;
                    font-weight: bold;
                    color: #7c3aed;
                    letter-spacing: 12px;
                    font-family: "Courier New", monospace;
                }
                .info-box {
                    background: #fef3c7;
                    border-left: 4px solid #f59e0b;
                    padding: 15px;
                    border-radius: 8px;
                    color: #92400e;
                    font-size: 13px;
                    line-height: 1.6;
                    margin-bottom: 25px;
                }
                .footer {
                    text-align: center;
                    padding: 25px 30px;
                    background: #f9fafb;
                    border-top: 1px solid #e5e7eb;
                }
                .footer p {
                    color: #6b7280;
                    font-size: 13px;
                    line-height: 1.6;
                }
                .footer a {
                    color: #7c3aed;
                    text-decoration: none;
                }
                .copyright {
                    margin-top: 10px;
                    font-size: 12px;
                    color: #9ca3af;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="logo">🎬</div>
                    <h1>' . SITE_NAME . '</h1>
                    <p>邮箱验证码</p>
                </div>
                <div class="content">
                    <div class="greeting">
                        ' . ($username ? "<p>尊敬的 <strong>{$username}</strong>：</p>" : "<p>您好：</p>") . '
                        <p style="margin-top: 10px;">您正在进行邮箱验证操作，您的验证码如下：</p>
                    </div>
                    <div class="code-box">
                        <div class="code-title">您的验证码（5分钟内有效）</div>
                        <div class="code">' . $code . '</div>
                    </div>
                    <div class="info-box">
                        <strong>⚠️ 安全提示：</strong><br>
                        1. 请不要将验证码告诉任何人，包括自称' . SITE_NAME . '的工作人员。<br>
                        2. 此验证码仅用于本次操作，过期无效。<br>
                        3. 如果您没有进行此操作，请忽略此邮件。
                    </div>
                </div>
                <div class="footer">
                    <p>此邮件由系统自动发送，请勿直接回复</p>
                    <p class="copyright">© ' . date("Y") . ' ' . SITE_NAME . ' 版权所有</p>
                </div>
            </div>
        </body>
        </html>
        ';
    }

    public static function getBanEmailHtml($username, $banReason, $banStartTime, $banEndTime) {
        return '
        <!DOCTYPE html>
        <html lang="zh-CN">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . SITE_NAME . ' - 账号封禁通知</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    background: linear-gradient(135deg, #f43f5e 0%, #ef4444 100%);
                    min-height: 100vh;
                    padding: 40px 20px;
                }
                .container {
                    max-width: 500px;
                    margin: 0 auto;
                    background: #fff;
                    border-radius: 20px;
                    overflow: hidden;
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                }
                .header {
                    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                    padding: 40px 30px;
                    text-align: center;
                    color: #fff;
                }
                .logo {
                    width: 70px;
                    height: 70px;
                    background: rgba(255,255,255,0.2);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 15px;
                    font-size: 36px;
                    backdrop-filter: blur(10px);
                }
                .header h1 {
                    font-size: 24px;
                    font-weight: 600;
                    margin-bottom: 8px;
                }
                .header p {
                    opacity: 0.9;
                    font-size: 14px;
                }
                .content {
                    padding: 40px 30px;
                }
                .greeting {
                    color: #1f2937;
                    font-size: 16px;
                    line-height: 1.6;
                    margin-bottom: 25px;
                }
                .greeting strong {
                    color: #ef4444;
                }
                .info-card {
                    background: #fef2f2;
                    border-radius: 16px;
                    padding: 25px;
                    margin-bottom: 20px;
                    border: 1px solid #fecaca;
                }
                .info-item {
                    display: flex;
                    justify-content: space-between;
                    padding: 12px 0;
                    border-bottom: 1px dashed #fecaca;
                }
                .info-item:last-child {
                    border-bottom: none;
                }
                .info-label {
                    color: #6b7280;
                    font-size: 14px;
                }
                .info-value {
                    color: #1f2937;
                    font-weight: 600;
                    font-size: 14px;
                    text-align: right;
                    max-width: 60%;
                }
                .info-value.danger {
                    color: #ef4444;
                }
                .info-value.warning {
                    color: #d97706;
                }
                .notice-box {
                    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
                    border-left: 4px solid #f59e0b;
                    padding: 15px;
                    border-radius: 8px;
                    color: #92400e;
                    font-size: 13px;
                    line-height: 1.7;
                    margin-bottom: 25px;
                }
                .notice-box strong {
                    display: block;
                    margin-bottom: 8px;
                    color: #b45309;
                }
                .footer {
                    text-align: center;
                    padding: 25px 30px;
                    background: #f9fafb;
                    border-top: 1px solid #e5e7eb;
                }
                .footer p {
                    color: #6b7280;
                    font-size: 13px;
                    line-height: 1.6;
                }
                .copyright {
                    margin-top: 10px;
                    font-size: 12px;
                    color: #9ca3af;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="logo">🚫</div>
                    <h1>账号封禁通知</h1>
                    <p>' . SITE_NAME . ' 账号安全中心</p>
                </div>
                <div class="content">
                    <div class="greeting">
                        <p>尊敬的 <strong>' . htmlspecialchars($username) . '</strong>：</p>
                        <p style="margin-top: 10px;">很抱歉地通知您，您的账号因违反平台规则已被封禁。封禁详情如下：</p>
                    </div>
                    <div class="info-card">
                        <div class="info-item">
                            <span class="info-label">封禁原因</span>
                            <span class="info-value danger">' . htmlspecialchars($banReason) . '</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">封禁时间</span>
                            <span class="info-value warning">' . $banStartTime . '</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">解除时间</span>
                            <span class="info-value warning">' . $banEndTime . '</span>
                        </div>
                    </div>
                    <div class="notice-box">
                        <strong>📢 温馨提示：</strong>
                        请您仔细阅读平台规则，遵守社区规范。封禁期间您将无法登录账号、观看影视内容。
                        如对此封禁有异议，您可以通过网站反馈功能与我们联系。
                    </div>
                </div>
                <div class="footer">
                    <p>此邮件由系统自动发送，请勿直接回复</p>
                    <p class="copyright">© ' . date("Y") . ' ' . SITE_NAME . ' 版权所有</p>
                </div>
            </div>
        </body>
        </html>
        ';
    }
}
?>
