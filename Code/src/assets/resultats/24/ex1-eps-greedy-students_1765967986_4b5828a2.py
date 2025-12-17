# Illustration of the epsilon-greedy algorithm
# author : J.-P. Comet
# date of last modification: 20/10/2022
##################################################

import numpy as np
import matplotlib.pyplot as plt
  
# Define Gaussian class
#     this class allows the simulation of the Gaussian process (rewards)
###################################################################
class Gaussian:
  def __init__(self, m, sigma):
    self.m = ...
    self.sigma= ...
  
  # Choose a random reward associated with this Gaussian process
  def reward(self): 
    return ....
    
  # Returns a sample of size N 
  def sampling(self,N):
    [...]
    return(...)

# Define Model class
#     this class allows the approximation of action-values
##########################################################
class Model:
  def __init__(self):
    self.mean = ...
    self.N = ...
  
  # Update the action-value estimate
  def update(self, x):
    self.N += ... 
    self.mean = ...

# the epsilon-greedy algorithm
def eps_greedy(environment, models, eps, N):
  nbmodels=len(models)

  # epsilon greedy
  for i in range(N):
    p = [...choisir un nombre aleatoire dans [0,1]...]
    if p < eps:
      j = [... choix alearoire du nombre a jouer ... ]
    else:
      j = [... choix du modele ayant la plus grande estimation de la moyenne ... ]
    x = [... demander a l environnement la recompense en jouant j ...] 
    [... mise a jour du modele j ...] 

  # affichage des estimations des differentes moyennes  
  [... blabla ...]
  
  return ...  
  
if __name__ == '__main__':

  # initialisation
  print("First illustration of the epsilon-gready alfgorithm.")
  print("----------------------------------------------------")
  print("The environment gives rewards according to different Gaussian distributions.")
  print("The goal of the game is to choose the best Gaussian distribution.\n")
  NBGaussian=int(input("Enter a number of Gaussian distributions: "))
  NumberOfRuns=5000

  # initialisation de l'environnement qui a NBGaussian gaussiennes
  #    et des NBGaussian modeles associes
  environment=[]
  models=[]
  for i in range(NBGaussian):
     [... blabla ...]
        
  # let's start experiments
  c=[]
  epsilons = [0.1, 0.05, 0.01, 0.001]
  for eps in epsilons:
    c += [eps_greedy(environment, models, eps,  NumberOfRuns)]

