<?php
require_once('head.php');
?>

<body>
<div id="userindex">
<div class="container-fluid p-t-15">
  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="panel-heading font-bold">个人资料</div>
        <div class="card-body">
          <div class="edit-avatar text-center">
            <img src="https://q2.qlogo.cn/headimg_dl?dst_uin=<?=$userrow['user'];?>&spec=100" alt="头像" class="img-avatar img-thumbnail">
            <div class="m-t-10">
              <h4>{{row.nickname || row.user}}</h4>
              <div class="text-muted">
                <span class="badge bg-primary">UID: {{row.uid}}</span>
                <span class="badge bg-success">用户名: {{row.user}}</span>
              </div>
            </div>
          </div>
          
          <hr>
          
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>剩余积分</label>
                <input type="text" class="form-control" :value="row.money" disabled>
              </div>
              <div class="form-group">
                <label>总充值</label>
                <input type="text" class="form-control" :value="row.zcz" disabled>
              </div>
              <div class="form-group">
                <label>费率</label>
                <input type="text" class="form-control" :value="row.addprice" disabled>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="form-group">
                <label>邀请码</label>
                <input type="text" class="form-control" :value="row.yqm || '无'" disabled>
              </div>
              <div class="form-group">
                <label>邀请费率</label>
                <input type="text" class="form-control" :value="row.yqprice || '无'" disabled>
              </div>
              <div class="form-group">
                <label>上级代理</label>
                <input type="text" class="form-control" :value="row.sjuser || '无'" disabled>
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label>API密钥</label>
            <div class="input-group">
              <input :type="hide ? 'text' : 'password'" class="form-control" :value="hide ? row.key : (row.key ? '****************' : '未开通')" disabled>
              <div class="input-group-append">
                <button v-if="row.key" @click="hi" class="btn btn-outline-secondary">
                  <i :class="hide ? 'mdi mdi-eye-off' : 'mdi mdi-eye'"></i> {{hidebutton}}
                </button>
                <button v-if="row.key" @click="copyKey" class="btn btn-outline-secondary">
                  <i class="mdi mdi-content-copy"></i> 复制
                </button>
                <button v-if="row.key" @click="ghapi" class="btn btn-outline-secondary">
                  <i class="mdi mdi-refresh"></i> 更换
                </button>
                <button v-if="!row.key" @click="ktapi" class="btn btn-success">
                  <i class="mdi mdi-key"></i> 开通API
                </button>
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label>推送通知</label>
            <div class="input-group">
              <input type="text" class="form-control" :value="row.pushPlusToken || '未设置'" disabled>
              <div class="input-group-append">
                <button @click="setPushPlusToken" class="btn btn-primary">
                  {{row.pushPlusToken ? '修改Token' : '设置Token'}}
                </button>
              </div>
            </div>
            <small class="text-muted">使用PushShowDoc接收微信通知</small>
          </div>
          
          <div class="form-group">
            <label>邀请链接</label>
            <div class="input-group">
              <input type="text" class="form-control" :value="row.yqlj" disabled>
              <div class="input-group-append">
                <button @click="copyInviteLink" class="btn btn-primary">复制链接</button>
              </div>
            </div>
          </div>
          
          <div class="text-center m-t-20">
            <button @click="szyqprice" class="btn btn-primary m-r-10">
              <i class="mdi mdi-account-plus"></i> 设置邀请费率
            </button>
            <!--<button @click="connect_qq" class="btn btn-info" v-if="!row.qq_openid">-->
            <!--  <i class="mdi mdi-qqchat"></i> 绑定QQ-->
            <!--</button>-->
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<?php require_once("footer.php");?>
</body>
</html>

<script type="text/javascript">
var vm = new Vue({
  el: "#userindex",
  data: {
    row: null,
    hide: false,
    hidebutton: '显示'
  },
  methods: {
    copyKey: function() {
      try {
        navigator.clipboard.writeText(this.row.key);
        layer.msg('复制成功', {icon:1});
      } catch (err) {
        layer.msg('复制失败！请手动复制', {icon:2});
      }
    },
    copyInviteLink: function() {
      try {
        navigator.clipboard.writeText(this.row.yqlj);
        layer.msg('邀请链接已复制', {icon:1});
      } catch (err) {
        layer.msg('复制失败！请手动复制', {icon:2});
      }
    },
    hi: function() {
      this.hide = !this.hide;
      this.hidebutton = this.hide ? '隐藏' : '显示';
    },
    userinfo: function() {
      var load = layer.load(2);
      this.$http.post("/apisub.php?act=userinfo")
        .then(function(data) {
          layer.close(load);
          if(data.data.code == 1) {
            this.row = data.data;
          } else {
            layer.alert(data.data.msg, {icon:2});
          }
        });
    },
    ktapi: function() {
      layer.confirm('开通API接口需要5积分，确认开通吗？', {
        title: '开通API',
        icon:1,
        btn: ['确定开通', '取消']
      }, function() {
        var load = layer.load(2);
        axios.get("/apisub.php?act=ktapi&type=1")
          .then(function(data) {
            layer.close(load);
            if(data.data.code == 1) {
              layer.msg(data.data.msg, {icon:1});
              vm.userinfo();
            } else {
              layer.msg(data.data.msg, {icon:2});
            }
          });
      });
    },
    ghapi: function() {
      layer.confirm('更换API密钥后，旧的密钥将失效。确认更换吗？', {
        title: '更换API密钥',
        icon:1,
        btn: ['确定更换', '取消']
      }, function() {
        var load = layer.load(2);
        axios.get("/apisub.php?act=ktapi&type=3")
          .then(function(data) {
            layer.close(load);
            if(data.data.code == 1) {
              layer.msg(data.data.msg, {icon:1});
              vm.userinfo();
            } else {
              layer.msg(data.data.msg, {icon:2});
            }
          });
      });
    },
    szyqprice: function() {
      layer.prompt({
        title: '设置下级默认费率 (不能低于'+this.row.addprice+')',
        value: this.row.yqprice || this.row.addprice
      }, function(yqprice, index) {
        layer.close(index);
        var load = layer.load(2);
        $.post("/apisub.php?act=yqprice", {yqprice: yqprice}, function(data) {
          layer.close(load);
          if(data.code == 1) {
            vm.userinfo();
            layer.msg(data.msg, {icon:1});
          } else {
            layer.msg(data.msg, {icon:2});
          }
        }, 'json');
      });
    },
    setPushPlusToken: function() {
      layer.prompt({
        title: '设置PushShowDoc',
        value: this.row.pushPlusToken || ''
      }, function(token, index) {
        layer.close(index);
        var load = layer.load(2);
        $.post("/apisub.php?act=updatePushPlusToken", {pushPlusToken: token}, function(data) {
          layer.close(load);
          if(data.code == 1) {
            vm.userinfo();
            layer.msg('设置成功', {icon:1});
          } else {
            layer.msg(data.msg, {icon:2});
          }
        }, 'json');
      });
    },
    connect_qq: function() {
      var ii = layer.load(0, {shade: [0.1, '#fff']});
      $.ajax({
        type: "POST",
        url: "../qq_login.php",
        data: {"type": 'qq'},
        dataType: 'json',
        success: function(data) {
          layer.close(ii);
          if(data.code == 1) {
            window.location.href = data.url;
          } else {
            layer.alert(data.msg, {icon:7});
          }
        }
      });
    }
  },
  mounted() {
    this.userinfo();
  }
});
</script>