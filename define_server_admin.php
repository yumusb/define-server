<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Catch the SMTP settings
if (isset($_POST['define_server_update']) && isset($_POST['define_server_nonce_update'])) {
    if (!wp_verify_nonce(trim($_POST['define_server_nonce_update']), 'my_ws_nonce')) {
        wp_die('Security check not passed!');
    }
    $this->wsOptions = array();
    $this->wsOptions["downserver"] = sanitize_text_field( trim( $_POST['downserver'] ) );
    $this->wsOptions["apiserver"] = sanitize_text_field( trim( $_POST['apiserver'] ) );
    update_option("define_server_options", $this->wsOptions);
}
$ws_nonce = wp_create_nonce('my_ws_nonce');
?>
<div class="wrap">
    <h1>
        Define Your Wordpress Server
    </h1>

    <form action="" method="post" enctype="multipart/form-data" name="define_server_form">
        <table class="form-table"><br>
        	由于未知原因，Wordpress屏蔽了来自中国的访问，这就造成了服务器在中国的wordpress无法进行正常的更新与安装主题等功能。<br>根据网络现有资源，我“组合”出了一个插件来解决此问题。
            <tr valign="top">
                <th scope="row">
                    downserver<br>[downloads.wordpress.org]
                </th>
                <td>
                    <label>
                        <input type="text" name="downserver" value="<?php echo $this->wsOptions["downserver"]; ?>" size="43"
                               style="width:272px;height:24px;" required/>
                    </label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    apiserver<br>[api.wordpress.org]
                </th>
                <td>
                    <label>
                        <input type="text" name="apiserver" value="<?php echo $this->wsOptions["apiserver"]; ?>"
                               size="43" style="width:272px;height:24px;" required />
                    </label>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="hidden" name="define_server_update" value="update"/>
            <input type="hidden" name="define_server_nonce_update" value="<?php echo $ws_nonce; ?>"/>
            <input type="submit" class="button-primary" name="Submit" value="Save Changes"/>
        </p>
        <h3>站在巨人的肩膀上</h3>
        	1.<a href="https://github.com/sunxiyuan/wp-china-yes" target="_blank">wp-china-yes</a> 这个插件可以直接替换掉服务器，不过没有自定义功能。<br>
        	2.<a href="https://wordpress.org/plugins/wp-smtp/" target="_blank">wp-smtp</a>	这个插件提供了简介的配置页面。<br>
        	所以我拿了1的替换服务器部分代码加上2的配置页面，组成了“DEFINE SERVER”。希望能帮助解决你的问题。

    </form>