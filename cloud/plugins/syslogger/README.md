# Syslogger

Logs events from the Logger object to the syslog. This plugin can really help with localhost vanilla development. You should be logging appropriate events using `Logger::event(...)`. If you enable this plugin then you'll be able to see those events by tailing the appropriate syslog.

## Setting up syslog on OSX

1. Create the following file `/etc/asl/com.vanilla.logger`
2. Paste the following into that file:

    ```
    # Facility com.vanilla.logger gets saved only in /var/log/vanilla.log
    ? [= Facility local0] claim only
    * file /var/log/vanilla.log mode=0640 compress format=std rotate=seq file_max=5M all_max=20M
    ? [<= Level debug] store
    ```
    
3. Make sure the log file exists.

    ```
    sudo touch /var/log/vanilla.log
    ```

4. In order for your changes to take effect you'll have to send an `HUP` signal to syslogd.

    ```
    sudo killall -HUP syslogd
    ```

5. To see the events logged in vanilla you just need to tail the `vanilla.log` file.

    ```
    tail -f /var/log/vanilla.log
    ```

## Config options

By default the syslogger logs in json format which includes a lot of information meant to be sent to a search engine. You can change this to just simple messages with the following config option:

```
'Plugins.Syslogger.MessageFormat' => 'message'
```

---
Copyright &copy; 2014 [Vanilla Forums Inc.](http://vanillaforums.com).
