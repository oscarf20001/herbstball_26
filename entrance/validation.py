import re

class Validation():
    def __init__(self) -> None:
        pass


    def isValidEmail(self, email):
        pattern = '^[a-zA-Z0-9.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$'
        return re.match(pattern, email) is not None
    
