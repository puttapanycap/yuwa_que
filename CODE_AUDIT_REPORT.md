# Code Audit Report

## Summary
- Total symbols: 233
- Unused symbols: 12
- Removed symbols: 38
- Duplicate groups: 0
- Consolidated symbols: 0
- Fixed symbols: 0

## Removed Symbols
| Name | File | Reason |
| --- | --- | --- |
| refreshSettings | monitor/display.php | Unused JavaScript function |
| getAppDescription | config/config.php | Unused configuration/accessor |
| getAppLogo | config/config.php | Unused configuration/accessor |
| getAppTimezone | config/config.php | Unused configuration/accessor |
| getAppLanguage | config/config.php | Unused configuration/accessor |
| getQueuePrefixLength | config/config.php | Unused configuration/accessor |
| getQueueNumberLength | config/config.php | Unused configuration/accessor |
| getMaxQueuePerDay | config/config.php | Unused configuration/accessor |
| getQueueTimeoutMinutes | config/config.php | Unused configuration/accessor |
| getDisplayRefreshInterval | config/config.php | Unused configuration/accessor |
| isPriorityQueueEnabled | config/config.php | Unused configuration/accessor |
| isAutoForwardEnabled | config/config.php | Unused configuration/accessor |
| getWorkingHoursStart | config/config.php | Unused configuration/accessor |
| getWorkingHoursEnd | config/config.php | Unused configuration/accessor |
| isWithinWorkingHours | config/config.php | Unused configuration/accessor |
| isTTSEnabled | config/config.php | Unused configuration/accessor |
| getTTSProvider | config/config.php | Unused configuration/accessor |
| getTTSLanguage | config/config.php | Unused configuration/accessor |
| getTTSVoice | config/config.php | Unused configuration/accessor |
| getTTSSpeed | config/config.php | Unused configuration/accessor |
| getTTSPitch | config/config.php | Unused configuration/accessor |
| getAudioVolume | config/config.php | Unused configuration/accessor |
| getAudioRepeatCount | config/config.php | Unused configuration/accessor |
| isSoundNotificationBeforeEnabled | config/config.php | Unused configuration/accessor |
| isEmailNotificationEnabled | config/config.php | Unused configuration/accessor |
| getMailHost | config/config.php | Unused configuration/accessor |
| getMailPort | config/config.php | Unused configuration/accessor |
| getMailUsername | config/config.php | Unused configuration/accessor |
| getMailPassword | config/config.php | Unused configuration/accessor |
| getMailEncryption | config/config.php | Unused configuration/accessor |
| getMailFromAddress | config/config.php | Unused configuration/accessor |
| getMailFromName | config/config.php | Unused configuration/accessor |
| isTelegramNotificationEnabled | config/config.php | Unused configuration/accessor |
| getTelegramBotToken | config/config.php | Unused configuration/accessor |
| getTelegramChatId | config/config.php | Unused configuration/accessor |
| getTelegramAdminChatId | config/config.php | Unused configuration/accessor |
| getTelegramGroupChatId | config/config.php | Unused configuration/accessor |
| getTelegramNotifyTemplate | config/config.php | Unused configuration/accessor |

## Consolidated
_None_

## Refactors
| File | Change | Why |
| --- | --- | --- |
| config/config.php | Removed numerous unused configuration helper functions | Reduce dead code |
| monitor/display.php | Removed unused refreshSettings function | Reduce dead code |

## Security Notes
No insecure patterns modified. Removal of unused code reduces potential attack surface.
