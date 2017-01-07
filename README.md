# Feed2Chatwork

Push notification to chatwork from rss feeds.

## Usage

```bash
$ bin/console feed2chatwork:integrate
```
## Configuration

```json
{
    "chatwork": {
        "token": "PUT_YOUR_TOKEN_HERE"
    },
    "feeds": [
        {
            "feed_url": "http:\/\/rss.cnn.com\/rss\/cnn_latest.rss",
            "room_id": 123456
        }
    ],
    "last_fetched": "2017-01-01 00:00:00"
}
```

| key | description |
| ---- | ---- |
| feeds.feed_url | Get items from this feed |
| feeds.room_id | Push items to this room on chatwork |

## References

* [How to get API Token (Japanese)](http://developer.chatwork.com/ja/authenticate.html)
