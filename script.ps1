# ????
param (
    [int]$interface,
    [int]$duration,
    [string]$outputFile
)

# tshark ??
$tsharkCommand = "tshark -i $interface -a duration:$duration -w $outputFile"

# ?? tshark ??
Invoke-Expression $tsharkCommand
