<?php
include('confing/common.php');

// 检查用户是否登录
if (!$islogin) {
    @header('Content-Type: text/html; charset=UTF-8');
    exit("<script language='javascript'>alert('请先登录！');window.location.href='index';</script>");
}

// 检查是否为管理员
if ($userrow['uid'] != 1) {
    @header('Content-Type: text/html; charset=UTF-8');
    exit("<script language='javascript'>alert('权限不足，只有管理员可以访问此页面！');window.location.href='index';</script>");
}

// Chanify配置
$chanify_token = "CIDPrsUGEiJBQlpJWjNHR1QzSVNBSVg1SEtOTkhNMktURVJLRlRGT0RJIggIAhoEamdqaw.e4Ho4nARWrIGjDBQA9Ou1cnFdDF2L8akvqOXlIhrUMY"; // 替换为您的Chanify令牌
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>价格监控 - 开发中 - <?php echo $conf['sitename']; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- Animate.css -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet" integrity="sha512-c42qTSw/wPZ3/5LBzD+Bw5f7bSF2oxou6wEb+I/lqeaKV5FDIfMvvRp772y4jcJLKuGUOpbJMdg/BTl50fJYAw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- Font Awesome CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- 使用相同的样式文件 -->
    <link href="assets/css/huoyuan.css" rel="stylesheet">
    <style>
        .loading-spinner-large {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(0,123,255,.3);
            border-radius: 50%;
            border-top-color: #007bff;
            animation: spin 1s ease-in-out infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .countdown-text {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 1rem;
        }
        #feedbackModal .modal-body {
            padding: 1.5rem;
        }
        #feedbackText {
            min-height: 150px;
        }
        .feedback-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="container-fluid main-container">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-11">
                <div class="card main-card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>价格监控功能
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center" style="padding: 3rem 2rem;">
                            <div class="loading-spinner-large"></div>
                            <h5 class="text-primary animate__animated animate__fadeIn">价格监控功能正在开发中...</h5>
                            <p class="text-muted animate__animated animate__fadeIn animate__delay-1s">我们正在努力为您打造智能价格监控功能</p>
                            <div class="countdown-text animate__animated animate__fadeIn animate__delay-2s">
                                <span id="countdown">60</span>秒后自动返回上一页
                            </div>
                            <div class="mt-4 animate__animated animate__fadeIn animate__delay-3s">
                                <a href="javascript:history.back()" class="btn btn-primary me-2">
                                    <i class="fas fa-arrow-left me-2"></i>立即返回
                                </a>
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                                    <i class="fas fa-comment-dots me-2"></i>提意见
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 意见反馈模态框 -->
    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="feedbackModalLabel">
                        <i class="fas fa-comment-medical me-2"></i>意见反馈
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="feedbackForm">
                        <div class="mb-3">
                            <label for="feedbackText" class="form-label">您的宝贵意见：</label>
                            <textarea class="form-control" id="feedbackText" placeholder="请描述您对价格监控功能的建议或意见..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="contactInfo" class="form-label">联系方式（可选）：</label>
                            <input type="text" class="form-control" id="contactInfo" placeholder="邮箱/电话/QQ等，方便我们回复您">
                        </div>
                    </form>
                </div>
                <div class="modal-footer feedback-footer">
                    <small class="text-muted">您的意见将帮助我们改进产品</small>
                    <div>
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-primary" id="sendFeedbackBtn">
                            <span id="sendBtnText">发送</span>
                            <span id="sendingSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js"></script>
    
    <script>
        // 倒计时功能
        $(document).ready(function() {
            let seconds = 60;
            const countdownElement = $('#countdown');
            
            const timer = setInterval(() => {
                seconds--;
                countdownElement.text(seconds);
                
                if (seconds <= 0) {
                    clearInterval(timer);
                    window.history.back();
                }
            }, 1000);

            // 发送反馈
            $('#sendFeedbackBtn').click(function() {
                const feedback = $('#feedbackText').val().trim();
                const contact = $('#contactInfo').val().trim();
                
                if (!feedback) {
                    showToast('请输入反馈内容', 'error');
                    return;
                }
                
                // 显示发送中状态
                $('#sendingSpinner').removeClass('d-none');
                $('#sendBtnText').text('发送中...');
                $(this).prop('disabled', true);
                
                // 发送反馈到Chanify
                $.ajax({
                    url: 'send_feedback.php', // 创建一个新的PHP文件处理发送
                    type: 'POST',
                    data: {
                        feedback: feedback,
                        contact: contact,
                        token: '<?php echo $chanify_token; ?>',
                        user: '<?php echo $userrow["username"]; ?>'
                    },
                    success: function(response) {
                        $('#sendingSpinner').addClass('d-none');
                        $('#sendBtnText').text('发送');
                        $('#sendFeedbackBtn').prop('disabled', false);
                        
                        if (response.success) {
                            showToast('反馈已发送，感谢您的意见！', 'success');
                            $('#feedbackModal').modal('hide');
                            $('#feedbackText').val('');
                            $('#contactInfo').val('');
                        } else {
                            showToast('发送失败: ' + response.message, 'error');
                        }
                    },
                    error: function() {
                        $('#sendingSpinner').addClass('d-none');
                        $('#sendBtnText').text('发送');
                        $('#sendFeedbackBtn').prop('disabled', false);
                        showToast('网络错误，请稍后再试', 'error');
                    }
                });
            });
            
            // 显示Toast通知
            function showToast(message, type) {
                let background = type === 'success' ? '#28a745' : '#dc3545';
                Toastify({
                    text: message,
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "center",
                    backgroundColor: background,
                }).showToast();
            }
        });
    </script>
</body>
</html>