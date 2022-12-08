import threading
import subprocess
from time import sleep
from model.database_conn import DatabaseConn

stream_is_runing = []
threading_streams = {}

def thread_stream(id_stream, rtsp_url, live_url, key):
    try:
        print('Start Stream: ' + id_stream)
        print('RTSP URL: ' + rtsp_url)
        print('Live URL: ' + live_url)
        print('Key: ' + key)
        print("StreamingTask is running")
        ffcmd = f"ffmpeg -an -rtsp_transport tcp -stimeout 30000000 -i {rtsp_url} -tune zerolatency -vcodec libx264 -pix_fmt + -c:v copy -f flv rtmp://127.0.0.1/live/{live_url}?API_KEY={key}"
        cmd = ffcmd.split()
        process = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.STDOUT,universal_newlines=True)
        while True:
            if id_stream not in stream_is_runing:
                print('Stop Stream: ' + id_stream)
                threading_streams.pop(id_stream, None)
                subprocess.Popen.kill(process)
                break
            sleep(0.5)
    except:
        print('error restreaming')
            
def main():
    while True:
        stream_state = {}
        stream_store = {}
        db_conn = DatabaseConn().DB_CONN
        db_cursor = db_conn.cursor()
        db_cursor.execute('SELECT * FROM restream WHERE restream.exp < NOW()')
        result_streaming = db_cursor.fetchall()
        for x in result_streaming:
            stream_state[x[0]] = False
            stream_store[x[0]] = x
            try:
                stream_is_runing.remove(x[0])
            except:
                pass
            sleep(0.25)
        sleep(0.25)
        db_cursor.close()
        
        db_conn = DatabaseConn().DB_CONN
        db_cursor = db_conn.cursor()
        db_cursor.execute('SELECT * FROM restream WHERE restream.exp > NOW()')
        result_streaming = db_cursor.fetchall()
        for x in result_streaming:
            stream_state[x[0]] = True
            stream_store[x[0]] = x
            sleep(0.25)
        sleep(0.25)
        db_cursor.close()
        
        # print(stream_store['SID-000001'])
        
        for key in stream_state:
            if stream_state[key] == True:
                if key not in stream_is_runing:
                    stream_is_runing.append(key)
                    threading_streams[key] = threading.Thread(target=thread_stream, args=(key, stream_store[key][1], stream_store[key][2], stream_store[key][3]))
                    threading_streams[key].daemon = True
                    threading_streams[key].start()
            sleep(0.25)
        sleep(0.25)
if __name__ == '__main__':
    main()
    
