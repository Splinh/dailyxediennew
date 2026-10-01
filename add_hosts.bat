@echo off
:: Batch script to add dailynew.test to Windows hosts file
>nul 2>&1 "%SYSTEMROOT%\system32\cacls.exe" "%SYSTEMROOT%\system32\config\system"
if '%errorlevel%' NEQ '0' (
    echo Dang yeu cau quyen Administrator...
    goto UACPrompt
) else ( goto gotAdmin )

:UACPrompt
    echo Set UAC = CreateObject^("Shell.Application"^) > "%temp%\getadmin.vbs"
    echo UAC.ShellExecute "%~s0", "", "", "runas", 1 >> "%temp%\getadmin.vbs"
    "%temp%\getadmin.vbs"
    exit /B

:gotAdmin
    if exist "%temp%\getadmin.vbs" ( del "%temp%\getadmin.vbs" )
    pushd "%CD%"
    CD /D "%~dp0"

echo Dang them dailynew.test vao hosts...
findstr /i "dailynew.test" "%windir%\System32\drivers\etc\hosts" >nul
if %errorlevel% equ 0 (
    echo [OK] dailynew.test da co san trong file hosts!
) else (
    echo 127.0.0.1      dailynew.test        #laragon magic!>> "%windir%\System32\drivers\etc\hosts"
    echo [THANH CONG] Da them "127.0.0.1 dailynew.test" vao file hosts!
)
ipconfig /flushdns
echo.
echo Ban da co the truy cap http://dailynew.test ngay bay gio!
pause
