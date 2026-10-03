# Mixtend カレンダー

Mixtend の `schedule.json` を取得し、Figma のデザインどおりのカレンダーとして日本時間で表示する。

- 仕様: https://mixtend.notion.site/29aca0461512809b9f20e05bfc0a475c
- API: https://mixtend.github.io/schedule.json
- デザイン: https://www.figma.com/file/KxCKgxI4pGhBeRd3Up2EHI/Mixtend-Engineer-Recruitment-Test-Calendar-UI?node-id=0%3A1

## 評価基準への対応

| 評価基準                   | 対応                                                                                    | 主なファイル                                                                                                                   |
| -------------------------- | --------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| API 連携                   | 取得・検証・日本時間への変換をバックエンドで行い、整えた props を渡す。API の障害は 502 | [`app/Actions/`](app/Actions/)                                                                                                 |
| UI デザイン通りの実装      | Figma に合わせたベースラインと 1440px 幅の VRT で比較し、ずれを検知する                 | [`resources/js/features/schedule/`](resources/js/features/schedule/)                                                           |
| コードの可読性と構造       | Action クラスで責務を分け、PHP から TypeScript まで型をつなぐ                           | [設計判断](#設計判断)                                                                                                          |
| ボーナス: User-Agent       | すべてのリクエストを `Mixtend Coding Test` で送る                                       | [`GetMixtendHttpClientAction.php`](app/Actions/Mixtend/GetMixtendHttpClientAction.php)                                         |
| ボーナス: レスポンスのログ | すべてのレスポンスを `storage/logs/mixtend.log` に JSON で記録する                      | [`SendMixtendRequestAction.php`](app/Actions/Mixtend/SendMixtendRequestAction.php)、[`config/logging.php`](config/logging.php) |

## 環境構築

前提: [mise](https://mise.jdx.dev/)（シェルで有効化済み）、Docker（VRT のみ）

```sh
mise install       # PHP 8.5・Composer・Node 24
composer setup     # 依存のインストール、.env の作成、SQLite のマイグレーション、ビルド
composer run dev   # 開発サーバーの起動
```

mise を使わない場合は、PHP 8.5・Composer・Node 24 を用意して `composer setup` から始める。

ドメインのデータは持たないが、セッション・キャッシュ・キューが database ドライバーを使うため、`composer setup` が SQLite を作る。

## アクセス

http://localhost:8000

## コマンド

| コマンド                  | 内容                                                            |
| ------------------------- | --------------------------------------------------------------- |
| `composer test`           | pint・PHPStan・Pest                                             |
| `composer ci:check`       | 上記に加え、フロントエンドの lint・フォーマット・型チェック     |
| `npm run test:vrt`        | VRT（Docker 上の Playwright）                                   |
| `npm run test:vrt:update` | VRT のベースラインを更新する                                    |
| `composer generate-types` | laravel-data からの TypeScript 型、Wayfinder を生成して整形する |

## 仮定

### API

- レスポンスは信頼境界とみなし、形式と時刻の順序（終了が開始より後）を検証する
- 通信または形式の失敗は、部分的に描画せずページ全体を 502 にする
- キャッシュしない。API は `max-age=600` を返すが、リクエストのたびに取得する
- タイムアウトは 5 秒、リトライしない

### 時刻

- 表示のタイムゾーンは Asia/Tokyo に固定する
- 勤務時間は日本時間とみなし、変換しない

### 表示

- 列はミーティングのある日付だけで、日付順に並べる
- ミーティングは勤務時間内に収まる
- 重なるミーティングのレイアウトは未実装。いまは warning を記録し、重ねて描画する。終了と開始が同時刻なら重なりとみなさない
- 長い件名は省略し、全文は `title` 属性のツールチップで表示する

### 対象外

- モバイルのデザイン（`sm` を境に余白を詰め、横スクロールにする程度の対応）
- ダークモード
- ブラウザのタイムゾーンでの表示
- 認証

## 設計判断

### 技術選定

仕様は「PHP 7.3 以上」「簡単なカレンダー UI」だが、PHP 8.5・Laravel 13・Inertia v3・laravel-data による型生成・VRT・CI を入れた。画面や機能を足すときに、同じ構造のまま広げられるようにするため。API の境界から Vue の props まで型がつながり、デザインとの一致は Figma に合わせたベースラインとの VRT が守る。使い捨てのツールなら削る。

### Action クラス

`run()` だけを公開する素の PHP クラス（フレームワークの基底クラスを継承しない）。

- 依存はコンストラクタで受け取るので、DI コンテナで解決でき、依存がシグネチャから一目でわかる
- 1 クラス 1 責務。処理が複雑になったら、小さな Action に分けて組み合わせる

```
ScheduleIndexController
└ GetScheduleAction              日本時間への変換・並べ替え・重なりの検知
  └ GetMixtendScheduleAction     レスポンスの検証
    └ SendMixtendRequestAction   送信・ログ・例外への変換
      └ GetMixtendHttpClientAction  ベース URL・User-Agent・タイムアウト
```

エンドポイントを増やすときは [`MixtendRoute`](app/Enums/MixtendRoute.php) に case と `method()` の対応を足し、検証する薄い Action を 1 つ書けばよい。ベース URL は `MIXTEND_BASE_URL` で差し替えられ、ステージングやモックに向けられる。

### その他

- **例外を通信と形式で分ける** — `MixtendHttpException` と `MixtendScheduleException`。障害と API の仕様変更をログで見分けるため。前者は完全な URL をコンテキストに持ち curl で再現でき、後者は検証エラーを持つ
- **検証は Laravel の Validator とカスタムルール** — laravel-data の検証は日付をキーとするマップに合わないため。キーは [`MixtendMeetingsByDateRule`](app/Rules/MixtendMeetingsByDateRule.php) で検証する
- **型を PHP から TypeScript へ生成する** — laravel-data の Response クラスから [`resources/js/generated/types.ts`](resources/js/generated/types.ts) を生成する。生成物が古ければ CI が落ちる
- **フロントエンドは `features/<ドメイン>` にまとめる** — 画面に属するコンポーネントとテストを同じ場所に置く。`pages/` は Inertia のページの入口
- **テストの境界は 2 つ** — バックエンドはフィーチャーテスト（HTTP をフェイクし、props・ログ・ステータスを検証）、フロントエンドは VRT。内部の構造に依存しないので、自由にリファクタできる。VRT は Figma に合わせたベースラインとの一致を守り、AI エージェントが自分の変更を確かめる手段にもなる
- **エラーページは同じ URL のまま描画する** — リダイレクトしないので、再読み込みで API の取得を再試行できる。403・404・500・503 も同じエラーページにする

## AI の活用

Claude Opus 5.5 と GPT-6.1-Sol を使って開発した。設計の判断とレビューは自分で行い、Issue ごとに PR を作ってレビューを重ねた。

- ワークフローは [Matt Pocock の skills](https://www.aihero.dev/skills) に沿っている。`AGENTS.md` と `docs/agents` はその設定
- VRT は、エージェントが UI の変更を自分で確かめるためのフィードバックループとして入れた
- 計画と判断の経緯は [Issue](https://github.com/adwinying/mixtend/issues) と、それにリンクした PR に残している
