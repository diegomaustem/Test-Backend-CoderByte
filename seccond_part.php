<?php 
  define("POSTSENDPOINT", 'http://127.0.0.1:8000/api/all-posts');
  define("COMMENTSENDPOINT", 'http://127.0.0.1:8000/api/all-comments');

  $dataPosts        = getEndPointData(POSTSENDPOINT, 'posts', $filteredPostsIds=null);
  $filteredPostsIds = array_column($dataPosts, 'id');
  $dataComments     = getEndPointData(COMMENTSENDPOINT, 'comments', $filteredPostsIds);

  dataProcessing($dataPosts, $dataComments);

  function getEndPointData($endPoint, $type, $filteredPostsIds)
  {
    if($type =='posts') {
        $posts = curl_init($endPoint);
        curl_setopt($posts, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($posts, CURLOPT_HEADER, 0);
        $allPosts = json_decode(curl_exec($posts), true);
        curl_close($posts);

        $filters = [
            'userId'    => 1,
            'startDate' => '2021-01-02 00:00:00',
            'endDate'   => '2024-01-02 00:00:00'
        ];

        $filteredPosts = array_filter($allPosts, function($post) use($filters){
            return 
              $post['userId'] == $filters['userId'] &&
              $post['created_at'] >= $filters['startDate'] &&
              $post['created_at'] <= $filters['endDate'];
        });   
        
        usort($filteredPosts, function($a, $b){
            return $a['id'] <=> $b['id'];
        });

        return $filteredPosts;
    }

    if($type == 'comments') {
        $comments = curl_init($endPoint);
        curl_setopt($comments, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($comments, CURLOPT_HEADER, 0);
        $allComments = json_decode(curl_exec($comments), true);
        curl_close($comments);
  
        $filter = [
            'postId' => NULL
        ];

        $filteredComments = array_filter($allComments, function($comment) use($filter, $filteredPostsIds){
            return $comment['postId'] != $filter['postId'] &&
            in_array($comment['id'], $filteredPostsIds);
        });  

        usort($filteredComments, function($a, $b){
            return $a['id'] <=> $b['id'];
        });

        return $filteredComments;
    }
  }

  function dataProcessing($dataPosts, $dataComments) 
  {
    foreach($dataPosts as $post => $key) {
        $filteredCommentsBelongsPost = array_filter($dataComments, function($comment) use($key){
          return $comment['postId'] === $key['id'];
        });
  
        if(!empty($filteredCommentsBelongsPost)) {
          array_push($key, $filteredCommentsBelongsPost);
  
          if($dataPosts[$post]['id'] === $key['id']) {
            $dataPosts[$post] = $key;
          }
        }
    }

    $filteredPostsWithSolelyComments = array_filter($dataPosts, function($item) {
        return isset($item[0]) && is_array($item[0]);
    });

    foreach ($filteredPostsWithSolelyComments as &$item) {
      if(isset($item[0]) && is_array($item[0])) {
        $item['comments'] = $item[0]; 
        unset($item[0]);
      }
      if(isset($item['comments']) && is_array($item['comments']) && !isset($item['comments'][0])){
        $item['comments'] = array_values($item['comments']);
      }
    }
    echo json_encode($filteredPostsWithSolelyComments);
  }


