const express = require('express');
const cors = require('cors');
const nodemailer = require('nodemailer');
const dotenv = require('dotenv');
const path = require('path');
const fs = require('fs');

// Load environment variables
dotenv.config();

const app = express();
const PORT = process.env.PORT || 5000;

// Middleware
app.use(cors({
    origin: ['http://localhost:8000', 'http://127.0.0.1:8000', 'http://localhost:3000'],
    credentials: true
}));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Create logs directory
const logDir = path.join(__dirname, 'logs');
if (!fs.existsSync(logDir)) {
    fs.mkdirSync(logDir);
}

// Health check
app.get('/api/health', (req, res) => {
    res.json({
        status: 'OK',
        message: 'Node.js server is running',
        timestamp: new Date().toISOString(),
        environment: process.env.NODE_ENV || 'development'
    });
});

// Test endpoint
app.get('/api/test', (req, res) => {
    res.json({
        success: true,
        message: 'API is working!',
        data: {
            server: 'Node.js',
            port: PORT,
            time: new Date().toISOString()
        }
    });
});

// Send email endpoint
app.post('/api/send-email', async (req, res) => {
    try {
        const { to, subject, body, studentName } = req.body;

        if (!to || !subject || !body) {
            return res.status(400).json({
                success: false,
                message: 'Missing required fields: to, subject, body'
            });
        }

        console.log('📧 Email Request:');
        console.log('  To:', to);
        console.log('  Subject:', subject);
        console.log('  Student:', studentName || 'N/A');

        const logEntry = {
            to,
            subject,
            body,
            studentName,
            timestamp: new Date().toISOString()
        };
        
        fs.appendFileSync(
            path.join(logDir, 'email_log.json'),
            JSON.stringify(logEntry) + '\n'
        );

        if (!process.env.EMAIL_USER || !process.env.EMAIL_PASS) {
            console.log('⚠️ Email credentials not configured. Running in test mode.');
            
            return res.json({
                success: true,
                message: 'Email saved to log (test mode)',
                testMode: true,
                logEntry: logEntry
            });
        }

        const transporter = nodemailer.createTransport({
            service: 'gmail',
            auth: {
                user: process.env.EMAIL_USER,
                pass: process.env.EMAIL_PASS
            }
        });

        const mailOptions = {
            from: `"GroupSync" <${process.env.EMAIL_USER}>`,
            to: to,
            subject: subject,
            html: `
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 10px;">
                    <div style="background: linear-gradient(135deg, #2B7A78, #3AAFA9); padding: 20px; border-radius: 10px 10px 0 0; color: white;">
                        <h1 style="margin: 0;">📚 GroupSync</h1>
                        <p style="margin: 5px 0 0; opacity: 0.8;">Academic Collaboration Platform</p>
                    </div>
                    <div style="background: white; padding: 20px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        ${studentName ? `<h2>Hello ${studentName},</h2>` : '<h2>Hello,</h2>'}
                        <p style="font-size: 16px; line-height: 1.6; color: #333;">${body}</p>
                        <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
                        <p style="color: #999; font-size: 12px; text-align: center;">
                            This email was sent from GroupSync Professor Dashboard.<br>
                            ${new Date().toLocaleString()}
                        </p>
                    </div>
                </div>
            `
        };

        const info = await transporter.sendMail(mailOptions);
        console.log('✅ Email sent:', info.messageId);

        res.json({
            success: true,
            message: 'Email sent successfully',
            messageId: info.messageId
        });

    } catch (error) {
        console.error('❌ Email error:', error);
        res.status(500).json({
            success: false,
            message: 'Failed to send email',
            error: error.message
        });
    }
});

// Send feedback email
app.post('/api/send-feedback', async (req, res) => {
    try {
        const { studentEmail, studentName, feedbackType, feedbackMessage } = req.body;

        const subject = `📝 Feedback from Professor - ${feedbackType}`;
        const body = `
            <p><strong>Type:</strong> ${feedbackType}</p>
            <p><strong>Message:</strong></p>
            <p>${feedbackMessage}</p>
            <br>
            <p style="color: #666; font-size: 14px;">
                Please take this feedback constructively to improve your performance.
            </p>
        `;

        console.log('📝 Feedback Request:');
        console.log('  To:', studentEmail);
        console.log('  Type:', feedbackType);
        console.log('  Student:', studentName || 'N/A');

        const logEntry = {
            to: studentEmail,
            studentName,
            feedbackType,
            feedbackMessage,
            timestamp: new Date().toISOString()
        };
        
        fs.appendFileSync(
            path.join(logDir, 'feedback_log.json'),
            JSON.stringify(logEntry) + '\n'
        );

        if (!process.env.EMAIL_USER || !process.env.EMAIL_PASS) {
            return res.json({
                success: true,
                message: 'Feedback saved to log (test mode)',
                testMode: true
            });
        }

        const transporter = nodemailer.createTransport({
            service: 'gmail',
            auth: {
                user: process.env.EMAIL_USER,
                pass: process.env.EMAIL_PASS
            }
        });

        const mailOptions = {
            from: `"GroupSync" <${process.env.EMAIL_USER}>`,
            to: studentEmail,
            subject: subject,
            html: `
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 10px;">
                    <div style="background: linear-gradient(135deg, #2B7A78, #3AAFA9); padding: 20px; border-radius: 10px 10px 0 0; color: white;">
                        <h1 style="margin: 0;">📝 Feedback</h1>
                    </div>
                    <div style="background: white; padding: 20px; border-radius: 0 0 10px 10px;">
                        <h2>Hello ${studentName || 'Student'},</h2>
                        <p style="font-size: 16px; line-height: 1.6; color: #333;">
                            Your professor has provided the following feedback:
                        </p>
                        <div style="background: #f0f7f7; padding: 15px; border-radius: 8px; border-left: 4px solid #3AAFA9; margin: 15px 0;">
                            <p style="margin: 0; color: #333;">${feedbackMessage}</p>
                        </div>
                        <p style="font-size: 14px; color: #666;">
                            <strong>Type:</strong> ${feedbackType}
                        </p>
                        <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
                        <p style="color: #999; font-size: 12px; text-align: center;">
                            This is an automated message from GroupSync.<br>
                            ${new Date().toLocaleString()}
                        </p>
                    </div>
                </div>
            `
        };

        await transporter.sendMail(mailOptions);

        res.json({
            success: true,
            message: 'Feedback email sent successfully'
        });

    } catch (error) {
        console.error('Feedback email error:', error);
        res.status(500).json({
            success: false,
            message: 'Failed to send feedback email'
        });
    }
});

// Error handling
app.use((err, req, res, next) => {
    console.error('Server error:', err);
    res.status(500).json({
        success: false,
        message: 'Internal server error',
        error: process.env.NODE_ENV === 'development' ? err.message : undefined
    });
});

app.use((req, res) => {
    res.status(404).json({
        success: false,
        message: 'Endpoint not found'
    });
});

// Start server
app.listen(PORT, () => {
    console.log('═══════════════════════════════════════');
    console.log(`🚀 Server running on http://localhost:${PORT}`);
    console.log(`📧 Email service is ${process.env.EMAIL_USER ? '✅ configured' : '⚠️ in test mode'}`);
    console.log(`🔍 Health check: http://localhost:${PORT}/api/health`);
    console.log(`📝 Logs directory: ${logDir}`);
    console.log('═══════════════════════════════════════');
});