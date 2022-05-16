Feature:
  In order to test the api search endpoint
  As an anonymous user
  I want to call the search endpoint

  Scenario: Calling /api/search without params leads to 400 error
    When I request "/api/search" using HTTP GET
    Then the response code is 400
    And the response body is:
    """
    {"errors":{"date":"This value should not be null.","keyword":"This value should not be null."}}
    """

  Scenario: Calling /api/search with date param only leads to 400 error
    When I request "/api/search?date=2022-01-01" using HTTP GET
    Then the response code is 400
    And the response body is:
    """
    {"errors":{"keyword":"This value should not be null."}}
    """

  Scenario: Calling /api/search with keyword param only leads to 400 error
    When I request "/api/search?keyword=love" using HTTP GET
    Then the response code is 400
    And the response body is:
    """
    {"errors":{"date":"This value should not be null."}}
    """

  Scenario: Calling /api/search with all params will give a 200 answer
    When I request "/api/search?keyword=love&date=2022-05-16" using HTTP GET
    Then the response code is 200
# Commented for now => my behat does not take the test database into account but the dev one, need a bit of time to investigate this
#    And the response body matches:
#    """
#    /totalEvents\":1/
#    """
#    And the response body is:
#    """
#    {"meta":{"totalEvents":1,"totalPullRequests":0,"totalCommits":0,"totalComments":0},"data":{"events":[{"type":"MSG","url":"https:\/\/api.github.com\/repos\/yousign\/backend-test"}],"stats":[{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0,"MSG":1},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0},{"commit":0,"pullRequest":0,"comment":0}]}}
#    """

