<?php
namespace PHPMailer\PHPMailer;

class PHPMailer {
    const ENCRYPTION_SMTPS = 'ssl';
    const ENCRYPTION_STARTTLS = 'tls';

    public $Host = '';
    public $SMTPAuth = false;
    public $Username = '';
    public $Password = '';
    public $SMTPSecure = '';
    public $Port = 25;
    public $CharSet = 'iso-8859-1';
    public $From = '';
    public $FromName = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $isHTML = false;
    public $Timeout = 30;

    protected $to = [];
    protected $cc = [];
    protected $bcc = [];
    protected $replyTo = [];

    protected $smtpConnect = null;

    public function isSMTP() {
        // Use SMTP
    }

    public function setFrom($address, $name = '') {
        $this->From = $address;
        $this->FromName = $name;
        return true;
    }

    public function addAddress($address, $name = '') {
        $this->to[] = ['address' => $address, 'name' => $name];
        return true;
    }

    public function addCC($address, $name = '') {
        $this->cc[] = ['address' => $address, 'name' => $name];
        return true;
    }

    public function addBCC($address, $name = '') {
        $this->bcc[] = ['address' => $address, 'name' => $name];
        return true;
    }

    public function addReplyTo($address, $name = '') {
        $this->replyTo[] = ['address' => $address, 'name' => $name];
        return true;
    }

    public function isHTML($isHtml = true) {
        $this->isHTML = $isHtml;
    }

    public function send() {
        // Build headers
        $boundary = md5(uniqid(time()));
        $eol = "\r\n";

        $headers = [];
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'Return-Path: <' . $this->From . '>';
        $headers[] = 'From: ' . $this->encodeHeader($this->FromName) . ' <' . $this->From . '>';
        
        $toAddresses = [];
        foreach ($this->to as $t) {
            $toAddresses[] = ($t['name'] ? $this->encodeHeader($t['name']) . ' ' : '') . '<' . $t['address'] . '>';
        }
        $headers[] = 'To: ' . implode(', ', $toAddresses);

        if (!empty($this->cc)) {
            $ccAddresses = [];
            foreach ($this->cc as $c) {
                $ccAddresses[] = ($c['name'] ? $this->encodeHeader($c['name']) . ' ' : '') . '<' . $c['address'] . '>';
            }
            $headers[] = 'Cc: ' . implode(', ', $ccAddresses);
        }

        if (!empty($this->replyTo)) {
            $replyAddresses = [];
            foreach ($this->replyTo as $r) {
                $replyAddresses[] = ($r['name'] ? $this->encodeHeader($r['name']) . ' ' : '') . '<' . $r['address'] . '>';
            }
            $headers[] = 'Reply-To: ' . implode(', ', $replyAddresses);
        }

        $headers[] = 'Subject: ' . $this->encodeHeader($this->Subject);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'X-Mailer: PHPMailer Lite';

        if ($this->isHTML) {
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=' . $this->CharSet;
            $headers[] = 'Content-Transfer-Encoding: base64';
        }

        // Build body
        $body = '';
        if ($this->isHTML) {
            $body .= '--' . $boundary . $eol;
            $body .= 'Content-Type: text/plain; charset=' . $this->CharSet . $eol;
            $body .= 'Content-Transfer-Encoding: base64' . $eol . $eol;
            $body .= chunk_split(base64_encode($this->AltBody ? $this->AltBody : strip_tags($this->Body))) . $eol;
            
            $body .= '--' . $boundary . $eol;
            $body .= 'Content-Type: text/html; charset=' . $this->CharSet . $eol;
            $body .= 'Content-Transfer-Encoding: base64' . $eol . $eol;
            $body .= chunk_split(base64_encode($this->Body)) . $eol;
            
            $body .= '--' . $boundary . '--';
        } else {
            $body = chunk_split(base64_encode($this->Body));
        }

        // Try SMTP first
        if ($this->Host) {
            $result = $this->smtpSend($headers, $body);
            if ($result) return true;
        }

        // Fallback to native mail()
        $toStr = implode(', ', array_column($this->to, 'address'));
        return @mail($toStr, $this->Subject, $body, implode($eol, $headers));
    }

    protected function encodeHeader($str) {
        if ($this->CharSet === 'UTF-8' && preg_match('/[^\x20-\x7E]/', $str)) {
            return '=?UTF-8?B?' . base64_encode($str) . '?=';
        }
        return $str;
    }

    protected function smtpSend($headers, $body) {
        $host = $this->Host;
        $port = $this->Port;
        $secure = $this->SMTPSecure;
        
        if ($secure === self::ENCRYPTION_SMTPS) {
            $host = 'ssl://' . $host;
        }

        $this->smtpConnect = @fsockopen($host, $port, $errno, $errstr, $this->Timeout);
        if (!$this->smtpConnect) {
            return false;
        }

        stream_set_timeout($this->smtpConnect, $this->Timeout);
        $response = $this->getLines();
        
        if (strpos($response, '220') !== 0) {
            fclose($this->smtpConnect);
            return false;
        }

        // EHLO
        $this->sendLine('EHLO ' . gethostname());
        $response = $this->getLines();
        if (strpos($response, '250') !== 0) {
            fclose($this->smtpConnect);
            return false;
        }

        // STARTTLS if needed
        if ($secure === self::ENCRYPTION_STARTTLS) {
            $this->sendLine('STARTTLS');
            $response = $this->getLines();
            if (strpos($response, '220') === 0) {
                stream_socket_enable_crypto($this->smtpConnect, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->sendLine('EHLO ' . gethostname());
                $response = $this->getLines();
            }
        }

        // Auth
        if ($this->SMTPAuth) {
            $this->sendLine('AUTH LOGIN');
            $response = $this->getLines();
            if (strpos($response, '334') !== 0) {
                fclose($this->smtpConnect);
                return false;
            }

            $this->sendLine(base64_encode($this->Username));
            $response = $this->getLines();
            if (strpos($response, '334') !== 0) {
                fclose($this->smtpConnect);
                return false;
            }

            $this->sendLine(base64_encode($this->Password));
            $response = $this->getLines();
            if (strpos($response, '235') !== 0) {
                fclose($this->smtpConnect);
                return false;
            }
        }

        // MAIL FROM
        $this->sendLine('MAIL FROM: <' . $this->From . '>');
        $response = $this->getLines();
        if (strpos($response, '250') !== 0) {
            fclose($this->smtpConnect);
            return false;
        }

        // RCPT TO
        foreach ($this->to as $t) {
            $this->sendLine('RCPT TO: <' . $t['address'] . '>');
            $response = $this->getLines();
            if (strpos($response, '250') !== 0) {
                fclose($this->smtpConnect);
                return false;
            }
        }

        // DATA
        $this->sendLine('DATA');
        $response = $this->getLines();
        if (strpos($response, '354') !== 0) {
            fclose($this->smtpConnect);
            return false;
        }

        // Content
        $eol = "\r\n";
        $this->sendLine(implode($eol, $headers) . $eol . $eol . $body . $eol . '.');
        $response = $this->getLines();
        
        if (strpos($response, '250') !== 0) {
            fclose($this->smtpConnect);
            return false;
        }

        // QUIT
        $this->sendLine('QUIT');
        $this->getLines();
        fclose($this->smtpConnect);

        return true;
    }

    protected function sendLine($line) {
        fputs($this->smtpConnect, $line . "\r\n");
    }

    protected function getLines() {
        $data = '';
        while ($str = fgets($this->smtpConnect, 515)) {
            $data .= $str;
            if (isset($str[3]) && $str[3] == ' ') {
                break;
            }
        }
        return $data;
    }
}
?>
