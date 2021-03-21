<?php


namespace Tool;


use PHPMailer\PHPMailer\PHPMailer;

class Mail
{
    protected $option = [
        'Host' => 'smtp.163.com',
        'Username' => 'pandayevideo@163.com',
        'Password' => 'CXLTQUWDTPSAROQN',
        'SMTPSecure' => 'ssl',
        'Port' => 465,
        'FromName' => '爱播星球',
        'From' => 'pandayevideo@163.com',
    ];
    protected $mail;

    function __construct($option=[])
    {

        $mail = new PHPMailer();
        //是否启用smtp的debug进行调试 开发环境建议开启 生产环境注释掉即可 默认关闭debug调试模式
//        $mail->SMTPDebug = 1;

        //邮件正文是否为html编码 注意此处是一个方法 不再是属性 true或false
        $mail->isHTML(true);
        //设置发送的邮件的编码 可选GB2312  据说utf8在某些客户端收信下会乱码
        $mail->CharSet = 'UTF-8';
        //使用smtp鉴权方式发送邮件
        $mail->isSMTP();
        //smtp需要鉴权 这个必须是true
        $mail->SMTPAuth = true;
        // qq 邮箱的 smtp服务器地址，这里当然也可以写其他的 smtp服务器地址
        $mail->Host = $option['HOST'] ?? $this->option['Host'];
        //smtp登录的账号 这里填入字符串格式的qq号即可
        $mail->Username = $option['Username'] ?? $this->option['Username'];
        // 这个就是之前得到的授权码，一共16位
        $mail->Password = $option['Password'] ?? $this->option['Password'];
        //设置使用ssl加密方式登录鉴权
        $mail->SMTPSecure = $option['SMTPSecure'] ?? $this->option['SMTPSecure'];
        //设置ssl连接smtp服务器的远程服务器端口号，以前的默认是25，但是现在新的好像已经不可用了 可选465或587
        $mail->Port = $option['Port'] ?? $this->option['Port'];
        //设置发件人姓名（昵称） 任意内容，显示在收件人邮件的发件人邮箱地址前的发件人姓名
        $mail->FromName = $option['FromName'] ?? $this->option['FromName'];;
        //设置发件人邮箱地址
        $mail->From = $option['From'] ?? $this->option['From'];

        $this->mail = $mail;
    }

    function send($toaddr, $body, $subject = '')
    {
        if (is_array($toaddr)) {
            foreach ($toaddr as $v) {
                $this->mail->addAddress($v);
            }
        } else {
            $this->mail->addAddress($toaddr);
        }

        //添加该邮件的主题
        $this->mail->Subject = $subject;
        //添加邮件正文 上方将isHTML设置成了true，则可以是完整的html字符串 如：使用file_get_contents函数读取本地的html文件
        $this->mail->Body = $body;

        $status = $this->mail->send();
        //简单的判断与提示信息
        if($status) {
            return true;
        }else{
            return false;
        }
    }
}
//