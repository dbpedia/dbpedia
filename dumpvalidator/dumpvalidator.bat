@echo off
set APP_ROOT=%~p0
set CP="%APP_ROOT%."
call :findjars "%APP_ROOT%lib"
for %%f in (%1) do java -Xmx256M -cp %CP% DumpValidator "%%f"
exit /B

:findjars
for %%j in (%1\*.jar) do call :addjar "%%j"
for /D %%d in (%1\*) do call :findjars "%%d"
exit /B

:addjar
set CP=%CP%;%1
