- [ ] Confirmar alcance: correos transaccionales (recuperación de contraseña y welcome) con Brevo
- [ ] Ajustar Brevo integration: asegurar mailable y envío correcto
- [ ] Reemplazar en AuthController y/o mails existentes el uso de SMTP por Brevo (ya iniciado para RecoveryCodeMail/PasswordResetMail/WelcomeMail)
- [ ] Verificar que hay destinatario definido en las Mailables (uso de to correcto)
- [ ] Agregar variables .env necesarias: BREVO_API_KEY, MAIL_FROM_ADDRESS, MAIL_FROM_NAME
- [ ] Probar localmente con un usuario de prueba
- [ ] Loggear respuesta de Brevo y manejar errores/timeout
- [ ] Documentar pasos de setup (Brevo sender identity, SPF/DKIM/DMARC si aplica)

