<?php
require 'vendor/autoload.php';

class SearchElastic
{
    private $elasticclient = null;
    // private $conn = null;

    public function __construct()
    {

        $this->elasticclient = Elasticsearch\ClientBuilder::create()->build();
        
    }

    public function Mapping(){
        $params = ['index' => 'blog'];
        $response = $this->elasticclient->indices()->delete($params);
        $params = [
                    'index' => 'blog',
                    'body' => [
                        'mappings' => [
                            'article' => [
                                'properties' => [
                                    'id' => [
                                        'type' => 'integer'
                                     
                                    ],
                                    'article_name' => [
                                        'type' => 'string'
                                     
                                    ],
                                    'article_content' => [
                                        'type' => 'string'
                                     
                                    ],
                                    'article_url' => [
                                        'type' => 'string'
                                     
                                    ],
                                    'category_name' => [
                                        'type' => 'string'
                                     
                                    ],
                                    'username' => [
                                        'type' => 'string'
                                     
                                    ],
                                    'date' => [
                                        'type' => 'date',
                                        'format' => 'dd-MM-yyyy'
                                    ],
                                    'article_img' => [
                                        'type' => 'string'
                                     
                                    ],
                                ]
                            ]
                        ]
                    ]
                ];
       $this->elasticclient->indices()->create($params);
       
    }
    public function InsertData($conn)
    {
        $this->Mapping();
        $con    = $conn;
        $client = $this->elasticclient;
        $stmt   = "SELECT articles.article_id,articles.article_name,articles.article_content,articles.img,articles.url,categories.category_name,CONCAT(users.u_fname,' ',users.u_lname) AS username,DATE_FORMAT(articles.date,'%d-%m-%Y') AS dates FROM article INNER JOIN users ON users.user_id = article.user_Id INNER JOIN articles ON articles.article_id = article.article_id INNER JOIN categories ON categories.category_id = articles.category_id ";
        $result = $con->query($stmt);
        $params = null;

        while ($row = $result->fetch_assoc()) {
            $params['body'][] = array(
                'index' => array(
                    '_index' => 'blog',
                    '_type'  => 'article',
                    '_id'    => $row['article_id'],
                ),
            );

            $params['body'][] = [
                'article_name'    => $row['article_name'],
                'article_content' => $row['article_content'],
                'article_url'     => $row['url'],
                'category_name'   => $row['category_name'],
                'username'        => $row['username'],
                'date'            => $row['dates'],
                'article_img'     => $row['img'],
            ];
        }
        $responses = $client->bulk($params);
        
        return true;

    }

    public function InsertNode($articleid, $con)
    {
        $conn   = $con;
        $client = $this->elasticclient;
        $stmt   = "SELECT articles.article_id,articles.article_name,articles.article_content,articles.img,articles.url,categories.category_name,CONCAT(users.u_fname,' ',users.u_lname) AS username,DATE_FORMAT(articles.date,'%d-%m-%Y') AS dates FROM article INNER JOIN users ON users.user_id = article.user_Id INNER JOIN articles ON articles.article_id = article.article_id INNER JOIN categories ON categories.category_id = articles.category_id WHERE articles.article_id = $articleid";
        $result = $con->query($stmt);
        $params = null;

        while ($row = $result->fetch_assoc()) {
            $params = [
                'index' => 'blog',
                'type'  => 'article',
                'id'    => $row['article_id'],
                'body'  => [
                    'article_name'    => $row['article_name'],
                    'article_content' => $row['article_content'],
                    'article_url'     => $row['url'],
                    'category_name'   => $row['category_name'],
                    'username'        => $row['username'],
                    'date'            => $row['dates'],
                    'article_img'     => $row['img'],
                ]];
        }
        $responses = $client->index($params);

        return true;

    }

    public function UpdateNode($articleid, $con)
    {
        $conn   = $con;
        $client = $this->elasticclient;
        $stmt   = "SELECT articles.article_id,articles.article_name,articles.article_content,articles.img,articles.url,categories.category_name,CONCAT(users.u_fname,' ',users.u_lname) AS username,DATE_FORMAT(articles.date,'%d-%m-%Y') AS dates FROM article INNER JOIN users ON users.user_id = article.user_Id INNER JOIN articles ON articles.article_id = article.article_id INNER JOIN categories ON categories.category_id = articles.category_id WHERE articles.article_id = $articleid";
        $result = $con->query($stmt);
        $params = null;

        while ($row = $result->fetch_assoc()) {
            $params = [
                'index' => 'blog',
                'type'  => 'article',
                'id'    => $row['article_id'],
                'body'  => [
                    'article_name'    => $row['article_name'],
                    'article_content' => $row['article_content'],
                    'article_url'     => $row['article_id'],
                    'category_name'   => $row['category_name'],
                    'username'        => $row['username'],
                    'date'            => $row['dates'],
                    'article_img'     => $row['img'],
                ]];
        }
        $responses = $client->update($params);

        return true;

    }

    public function DeleteNode($id)
    {
        $client = $this->elasticclient;
        $params = [
            'index' => 'blog',
            'type'  => 'article',
            'id'    => $id,
        ];

        $responses = $client->delete($params);

        return true;

    }

    public function Search($query)
    {
        $client = $this->elasticclient;
        $result = array();

        $i = 0;

        $params = [
            'index' => 'blog',
            'type'  => 'article',
            'body'  => [
                'query' => [
                    'match' => ['article_content' => $query],
                ],
            ],
        ];
        $query                 = $client->search($params);
        $hits                  = sizeof($query['hits']['hits']);
        $hit                   = $query['hits']['hits'];
        $result['searchfound'] = $hits;
        while ($i < $hits) {

            $result['result'][$i] = $query['hits']['hits'][$i]['_source'];

            $i++;
        }

        return  $result;
    }

    public function limit_text($text)
    {
        $new  = strip_tags($text);
        $str2 = explode(' ', $new);
        //$str3 = explode('\r\n', $str2);
        //print_r($str2);
        $result = null;

        for ($i = 0; $i < sizeof($str2); $i++) {

            if ($i == 50) {
                break;
            }
            $result .= $str2[$i] . " ";

        }
        $result = str_replace('\r\n', '', $result);
        return $result;

    }

}
