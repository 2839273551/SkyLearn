<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统公告</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4bb543;
            --warning-color: #ffcc00;
            --danger-color: #f94144;
        }
        
        body {
            font-family: 'Noto Sans SC', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
        }
        
        .announcement-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .announcement-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }
        
        .announcement-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn-refresh {
            background-color: var(--light-color);
            color: var(--primary-color);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-refresh:hover {
            background-color: var(--primary-color);
            color: white;
            transform: rotate(360deg);
        }
        
        .timeline {
            position: relative;
            padding-left: 50px;
            list-style: none;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 20px;
            width: 2px;
            background: var(--primary-color);
            opacity: 0.2;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            animation: fadeIn 0.5s ease forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -40px;
            top: 5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 4px solid white;
            box-shadow: 0 0 0 2px var(--primary-color);
        }
        
        .timeline-item.pinned::before {
            background: var(--warning-color);
            box-shadow: 0 0 0 2px var(--warning-color);
        }
        
        .timeline-item.official::before {
            background: var(--danger-color);
            box-shadow: 0 0 0 2px var(--danger-color);
        }
        
        .timeline-date {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .timeline-date i {
            margin-right: 0.5rem;
        }
        
        .announcement-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .announcement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-weight: 600;
            margin: 0;
            color: var(--dark-color);
            font-size: 1.1rem;
        }
        
        .card-badges {
            display: flex;
            gap: 0.5rem;
        }
        
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
        }
        
        .badge-pinned {
            background-color: var(--warning-color);
            color: var(--dark-color);
        }
        
        .badge-official {
            background-color: var(--danger-color);
            color: white;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .card-content {
            color: #495057;
            line-height: 1.6;
            white-space: pre-line; /* 保留换行符 */
        }
        
        .card-footer {
            background-color: white;
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 0.75rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .load-more {
            text-align: center;
            margin-top: 2rem;
        }
        
        .btn-load-more {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-load-more:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
        
        .empty-state h4 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .search-box {
            position: relative;
            max-width: 400px;
            margin-bottom: 2rem;
        }
        
        .search-box input {
            padding-left: 2.5rem;
            border-radius: 50px;
            border: 1px solid rgba(0,0,0,0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .filter-btn {
            border: 1px solid rgba(0,0,0,0.1);
            background: white;
            color: #6c757d;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        
        .filter-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .filter-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .announcement-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .announcement-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .timeline {
                padding-left: 30px;
            }
            
            .timeline-item::before {
                left: -30px;
                width: 16px;
                height: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="announcement-container">
        <div class="announcement-header">
            <h1 class="announcement-title">
                <i class="fas fa-bullhorn me-2"></i>系统公告
            </h1>
            <div class="announcement-actions">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="搜索公告..." v-model="searchQuery" @input="filterAnnouncements">
                </div>
                <button class="btn-refresh" @click="refreshAnnouncements">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
        
        <div class="filter-buttons">
            <button class="filter-btn" :class="{active: filter === 'all'}" @click="setFilter('all')">
                全部公告
            </button>
            <button class="filter-btn" :class="{active: filter === 'pinned'}" @click="setFilter('pinned')">
                <i class="fas fa-thumbtack me-1"></i>置顶
            </button>
            <button class="filter-btn" :class="{active: filter === 'official'}" @click="setFilter('official')">
                <i class="fas fa-certificate me-1"></i>官方
            </button>
        </div>
        
        <ul class="timeline" v-if="filteredAnnouncements.length > 0">
            <li v-for="(res, index) in filteredAnnouncements" :key="res.id" 
                class="timeline-item" 
                :class="{'pinned': res.zhiding == '1', 'official': res.uid == 1}">
                <div class="timeline-date">
                    <i class="far fa-calendar-alt"></i>
                    {{res.time}}
                </div>
                <div class="announcement-card card">
                    <div class="card-header">
                        <h5 class="card-title">{{res.title}}</h5>
                        <div class="card-badges">
                            <span class="badge badge-pinned" v-if="res.zhiding == '1'">
                                <i class="fas fa-thumbtack"></i> 置顶
                            </span>
                            <span class="badge badge-official" v-if="res.uid == 1">
                                <i class="fas fa-certificate"></i> 官方
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-content" v-html="formatContent(res.content)"></div>
                    </div>
                    <div class="card-footer">
                        <div>
                            <i class="far fa-user me-1"></i>
                            {{res.uid == 1 ? '系统管理员' : '客服团队'}}
                        </div>
                    </div>
                </div>
            </li>
        </ul>
        
        <div class="empty-state" v-else>
            <i class="far fa-bell-slash"></i>
            <h4>暂无公告</h4>
            <p>当前没有可显示的公告内容</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
    <script>
        new Vue({
            el: ".announcement-container",
            data: {
                announcements: [],
                filteredAnnouncements: [],
                searchQuery: '',
                filter: 'all',
                isLoading: false
            },
            created() {
                this.loadAnnouncements();
                // 设置定时检查新公告（每分钟检查一次）
                setInterval(() => {
                    this.checkNewAnnouncements();
                }, 60000);
            },
            methods: {
                loadAnnouncements() {
                    if (this.isLoading) return;
                    
                    this.isLoading = true;
                    axios.get("/apisub.php?act=gglist")
                    .then(response => {
                        const data = response.data;
                        if (data.code == 1) {
                            this.announcements = data.data;
                            this.filteredAnnouncements = this.filterAnnouncements();
                        } else {
                            console.error('加载公告失败:', data);
                        }
                        this.isLoading = false;
                    })
                    .catch(error => {
                        console.error('加载公告失败:', error);
                        this.isLoading = false;
                    });
                },
                
                refreshAnnouncements() {
                    this.loadAnnouncements();
                    
                    // 添加旋转动画
                    const btn = document.querySelector('.btn-refresh');
                    btn.style.transform = 'rotate(360deg)';
                    setTimeout(() => {
                        btn.style.transform = 'rotate(0)';
                    }, 500);
                },
                
                checkNewAnnouncements() {
                    axios.get("/apisub.php?act=gglist")
                    .then(response => {
                        const data = response.data;
                        if (data.code == 1 && data.data.length > 0) {
                            const latestId = data.data[0].id;
                            if (this.announcements.length === 0 || latestId !== this.announcements[0].id) {
                                this.showNewAnnouncementNotification(data.data[0]);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('检查新公告失败:', error);
                    });
                },
                
                showNewAnnouncementNotification(announcement) {
                    // 使用浏览器的通知API或自定义通知
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification(`新公告: ${announcement.title}`, {
                            body: this.stripHtml(announcement.content).substring(0, 100) + '...'
                        });
                    }
                    
                    // 刷新公告列表
                    this.refreshAnnouncements();
                },
                
                formatContent(content) {
                    // 转换换行符为<br>标签
                    return content.replace(/\n/g, '<br>');
                },
                
                stripHtml(html) {
                    // 去除HTML标签
                    return html.replace(/<[^>]*>/g, '');
                },
                
                filterAnnouncements() {
                    let filtered = this.announcements;
                    
                    // 应用搜索过滤
                    if (this.searchQuery) {
                        const query = this.searchQuery.toLowerCase();
                        filtered = filtered.filter(item => 
                            item.title.toLowerCase().includes(query) || 
                            item.content.toLowerCase().includes(query)
                        );
                    }
                    
                    // 应用类型过滤
                    switch (this.filter) {
                        case 'pinned':
                            filtered = filtered.filter(item => item.zhiding == '1');
                            break;
                        case 'official':
                            filtered = filtered.filter(item => item.uid == 1);
                            break;
                    }
                    
                    return filtered;
                },
                
                setFilter(filterType) {
                    this.filter = filterType;
                    this.filteredAnnouncements = this.filterAnnouncements();
                }
            },
            watch: {
                searchQuery: _.debounce(function() {
                    this.filteredAnnouncements = this.filterAnnouncements();
                }, 300)
            }
        });
    </script>
</body>
</html>