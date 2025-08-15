using System;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Threading.Tasks;

class Program
{
    private static readonly object ConsoleLock = new object();

    static void Main()
    {
        // カレントディレクトリの *.png を取得
        var files = Directory.GetFiles(Directory.GetCurrentDirectory(), "*.png");

        var options = new ParallelOptions
        {
            MaxDegreeOfParallelism = 7 // 最大同時実行数
        };

        Parallel.ForEach(files, options, file =>
        {
            var fileName = Path.GetFileName(file);

            lock (ConsoleLock)
                Console.WriteLine($"START: {fileName}");

            var psi = new ProcessStartInfo
            {
                FileName = "optipng",
                Arguments = $"-strip all -o 7 \"{file}\"",
                CreateNoWindow = true,
                UseShellExecute = false
            };

            using (var proc = Process.Start(psi))
            {
                proc.WaitForExit();
                lock (ConsoleLock)
                    Console.WriteLine($"END  : {fileName} (ExitCode={proc.ExitCode})");
            }
        });

        Console.WriteLine("全ての PNG 最適化が完了しました。");
    }
}