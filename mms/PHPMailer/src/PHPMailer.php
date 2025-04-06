<?php

namespace PHPMailer\PHPMailer;

class PHPMailer {
    public $SMTPDebug = 0;
    public $Host = 'smtp.gmail.com';
    public $Port = 587;
    public $SMTPAuth = true;
    public $Username = '';
    public $Password = '';
    public $SMTPSecure = 'tls';
    public $From = '';
    public $FromName = '';
    public $Subject = '';
    public $Body = '';
    public $isHTML = true;
    private $recipients = [];

    public function isSMTP() {
        return true;
    }

    public function setFrom($email, $name = '') {
        $this->From = $email;
        $this->FromName = $name;
    }

    public function addAddress($email, $name = '') {
        $this->recipients[] = ['email' => $email, 'name' => $name];
    }

    public function send() {
        $to = implode(', ', array_column($this->recipients, 'email'));
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->FromName . ' <' . $this->From . '>',
            'X-Mailer: PHP/' . phpversion()
        ];

        return mail($to, $this->Subject, $this->Body, implode("\r\n", $headers));
    }
} 