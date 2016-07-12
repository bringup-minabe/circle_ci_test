# circle_ci_test

##事前設定
<https://github.com/CircleAround/git_web_hook>

##動作流れ
1. GitHubへPUSH
2. CircleCiがPUSHを検知し、ビルド開始
3. ビルド後CircleCiのWebhookよりデプロイをおこなうサーバーのhook.phpへhook
4. hook.phpでデプロイ処理

##Git
デプロイをおこなうサーバーからのgit接続時のportは**443**に変更

  <https://help.github.com/articles/using-ssh-over-the-https-port/>

##CircleCi
PROJECT SETTINGS -> Environment Variables でSECRET設定

##Apache
環境変数を使えるようにphp.iniの設定変更

    ; $_ENV が使えない設定の例
    variables_order = "GPCS"

    ; $_ENV が使える設定の例
    variables_order = "EGPCS"
