@echo off
@echo Running local test...

REM Navigate to project directory
cd /d "C:\Project\skm-ekp-app"

REM Build the project
@echo Building...
npm run build

REM Start a simple server to serve the build folder
@echo Starting local server on http://localhost:8080...
start /B "" "C:\Project\skm-ekp-app\node_modules\.bin\serve" -l tcp://0.0.0.0:8080 public/build

REM Wait a few seconds for server to start
timeout /t 3

REM Open browser to the trip creation page
@echo Opening browser...
start chrome "http://localhost:8080/trips/create?_v=20241119"

@echo Test complete! Check browser.
pause