import mysql.connector

class DatabaseConn:
    def __init__(self):
        self.DB_CONN = mysql.connector.connect(
            host = "127.0.0.1", 
            user = "chaiwit",
            passwd = "anorider",
            database="streaming"
        )