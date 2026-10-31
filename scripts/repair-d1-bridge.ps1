param(
    [Parameter(Mandatory)]
    [SecureString] $BridgeSecret,

    [Parameter(Mandatory)]
    [string] $KasirPassword,

    [string] $WorkerName = 'pombensin-d1-bridge',

    [string] $DatabaseName = 'pos-pombensin-db'
)

$secretPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($BridgeSecret)

try {
    $plainSecret = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($secretPointer)
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($secretPointer)
}

try {
    $plainSecret | npx wrangler secret put BRIDGE_SECRET_KEY --name $WorkerName
} finally {
    Remove-Variable plainSecret -ErrorAction SilentlyContinue
}

npx wrangler deploy

$hash = php -r 'echo password_hash($argv[1], PASSWORD_BCRYPT);' -- $KasirPassword

if ($hash -notmatch '^\$2[aby]\$\d{2}\$') {
    throw 'Gagal membuat hash Bcrypt untuk pengguna kasir.'
}

$seedSql = "UPDATE users SET password = '$hash', updated_at = datetime('now') WHERE name = 'kasir'; INSERT INTO users (name, password, created_at, updated_at) SELECT 'kasir', '$hash', datetime('now'), datetime('now') WHERE NOT EXISTS (SELECT 1 FROM users WHERE name = 'kasir');"
npx wrangler d1 execute $DatabaseName --remote --command $seedSql

php artisan config:clear
