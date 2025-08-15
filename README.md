# optall_parallel

PNG画像の並列最適化ツール

## 概要

`optall_parallel`は、カレントディレクトリ内のすべてのPNGファイルを並列処理で最適化するC#製のコマンドラインツールです。OptiPNGを使用して画像の品質を保ちながらファイルサイズを削減します。

## 特徴

- **並列処理**: 最大7つのファイルを同時に処理し、高速な最適化を実現
- **自動検出**: カレントディレクトリ内のすべての*.pngファイルを自動で検出
- **高品質最適化**: OptiPNGの最高レベル（-o 7）での最適化
- **メタデータ除去**: 不要なメタデータを削除してファイルサイズを削減
- **進捗表示**: 各ファイルの処理開始・終了をリアルタイムで表示

## 必要な環境

- Windows 10/11
- .NET Framework 4.8
- OptiPNG（システムのPATHに設定されている必要があります）

### OptiPNGのインストール

1. [OptiPNG公式サイト](http://optipng.sourceforge.net/)からWindows版をダウンロード
2. ダウンロードしたzipファイルを展開
3. `optipng.exe`を適当なフォルダ（例：`C:\Tools\optipng\`）に配置
4. システム環境変数のPATHに上記フォルダを追加

## ビルド方法

```cmd
# Visual Studioまたは
msbuild src/optall_parallel.sln

# またはVisual Studio Developer Command Promptで
cd src
msbuild
```

## 使用方法

1. 最適化したいPNGファイルがあるディレクトリに移動
2. プログラムを実行

```cmd
# 実行例
cd C:\path\to\png\files
optall_parallel.exe
```

### 実行例

```
START: image1.png
START: image2.png
START: image3.png
END  : image1.png (ExitCode=0)
START: image4.png
END  : image2.png (ExitCode=0)
END  : image3.png (ExitCode=0)
END  : image4.png (ExitCode=0)
全ての PNG 最適化が完了しました。
```

## 設定

### 並列処理数の変更

`Program.cs`の以下の部分を編集することで、同時実行数を変更できます：

```csharp
var options = new ParallelOptions
{
    MaxDegreeOfParallelism = 7 // この数値を変更
};
```

### OptiPNGオプションの変更

最適化レベルやオプションを変更したい場合は、以下の部分を編集してください：

```csharp
Arguments = $"-strip all -o 7 \"{file}\"",
```

- `-o 7`: 最適化レベル（0-7、7が最高品質）
- `-strip all`: すべてのメタデータを削除

## 注意事項

- 元のファイルは上書きされます（バックアップを取ることを推奨）
- OptiPNGが正しくインストールされ、PATHに設定されている必要があります
- 大量のファイルを処理する場合は、システムリソースの使用量にご注意ください

## ライセンス

CC0 1.0 Universal (CC0 1.0) Public Domain Dedication

このソフトウェアは、法的に可能な範囲で、著作権およびその他の権利を放棄し、パブリックドメインに提供されています。
詳細については、[CC0 1.0 Universal](https://creativecommons.org/publicdomain/zero/1.0/deed.ja)をご覧ください。

## 貢献

バグ報告や機能要望は、GitHubのIssuesでお知らせください。

## 更新履歴

### v1.0.0
- 初回リリース
- PNG画像の並列最適化機能
- 進捗表示機能
