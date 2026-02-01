using System;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Threading.Tasks;

class Program
{
    private static readonly object ConsoleLock = new object();

    // 定数定義（仕様値をそのまま明示）
    private const int MaxParallelism = 7; // 最大同時実行数
    private const string SearchPattern = "*.png"; // 対象ファイルパターン
    private const string OptimizerExe = "optipng"; // 利用コマンド
    private const string OptimizerArgsFormat = "--strip all -o 6 \"{0}\""; // 既存引数パターン

    static void Main()
    {
        var files = GetTargetFiles();
        var options = CreateParallelOptions();

        Parallel.ForEach(files, options, OptimizeSingleFile);

        WriteLineUnlocked("全ての PNG 最適化が完了しました。");
    }

    // 対象 PNG ファイル取得
    private static string[] GetTargetFiles()
    {
        return Directory.GetFiles(Directory.GetCurrentDirectory(), SearchPattern);
    }

    // ParallelOptions を生成
    private static ParallelOptions CreateParallelOptions()
    {
        return new ParallelOptions { MaxDegreeOfParallelism = MaxParallelism };
    }

    // 各ファイルの最適化処理
    private static void OptimizeSingleFile(string file)
    {
        var fileName = Path.GetFileName(file);
        WriteLineLocked($"START: {fileName}");

        var psi = CreateProcessStartInfo(file);

        using (var proc = Process.Start(psi))
        {
            // 元コードと同じく例外はそのまま外へ（仕様変更しない）
            proc.WaitForExit();
            WriteLineLocked($"END  : {fileName} (ExitCode={proc.ExitCode})");
        }
    }

    // ProcessStartInfo 生成
    private static ProcessStartInfo CreateProcessStartInfo(string file)
    {
        return new ProcessStartInfo
        {
            FileName = OptimizerExe,
            Arguments = string.Format(OptimizerArgsFormat, file),
            CreateNoWindow = true,
            UseShellExecute = false
        };
    }

    // ロック付き出力（元の lock + Console.WriteLine を集約）
    private static void WriteLineLocked(string message)
    {
        lock (ConsoleLock)
        {
            Console.WriteLine(message);
        }
    }

    // 完了メッセージは逐次処理後なのでロック不要（元仕様と同じ挙動）
    private static void WriteLineUnlocked(string message)
    {
        Console.WriteLine(message);
    }
}