<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Akun POS BISA KULAK</title>
</head>

<body style="margin:0;padding:0;background:#f4f6f9;font-family:Arial,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">

                <table width="500" cellpadding="0" cellspacing="0"
                    style="background:#ffffff;border-radius:10px;overflow:hidden;">

                    <tr>
                        <td align="center"
                            style="padding:24px;background:#2563eb;color:white;">

                            <h2 style="margin:0;">
                                BISA KULAK POS
                            </h2>

                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px;color:#333;">

                            <h2>
                                Akun POS Anda telah aktif
                            </h2>

                            <p>
                                Silakan login ke aplikasi POS menggunakan informasi berikut:
                            </p>

                            <table cellpadding="6" cellspacing="0">
                                <tr>
                                    <td><b>Email</b></td>
                                    <td>: {{ $user->email }}</td>
                                </tr>

                                <tr>
                                    <td><b>Password</b></td>
                                    <td>: {{ $password }}</td>
                                </tr>
                            </table>

                            <p>
                                Demi keamanan akun, silakan segera mengganti password
                                setelah berhasil login.
                            </p>

                            <p>
                                Silakan login melalui:
                            </p>

                            <p>
                                <a href="https://pos.bisakulak.my.id">
                                    Login POS BISA KULAK
                                </a>
                            </p>

                            <hr>

                            <p style="font-size:13px;color:#666;">
                                Email ini dikirim secara otomatis oleh sistem POS BISA KULAK.
                            </p>

                            <p>
                                Salam,<br>
                                <strong>System BISA KULAK</strong>
                            </p>

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>