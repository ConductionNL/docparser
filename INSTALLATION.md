# Installation
This document dives a little bit deeper into installing your component on a kubernetes cluster, looking for information on setting up your component on a local machine? Take a look at the [tutorial](TUTORIAL.md) instead. 

## Setting up helm



## Setting up tiller
Create the tiller service account:

```CLI
$ kubectl -n kube-system create serviceaccount tiller --kubeconfig="api/helm/kubeconfig.yaml"
```

Next, bind the tiller service account to the cluster-admin role:
```CLI
$ kubectl create clusterrolebinding tiller --clusterrole cluster-admin --serviceaccount=kube-system:tiller --kubeconfig="api/helm/kubeconfig.yaml"
```

Now we can run helm init, which installs Tiller on our cluster, along with some local housekeeping tasks such as downloading the stable repo details:
```CLI
$ helm init --service-account tiller --kubeconfig="kubeconfig.yaml"
```

To verify that Tiller is running, list the pods in the kube-system namespace:
```CLI
$ kubectl get pods --namespace kube-system --kubeconfig="kubeconfig.yaml"
```

The Tiller pod name begins with the prefix tiller-deploy-.

Now that we've installed both Helm components, we're ready to use helm to install our first application.


## Setting up ingress
We need at least one nginx controller per kubernetes kluster, doh optionally we could set on up on a per namebase basis

```CLI
$ helm install ingress-nginx/ingress-nginx --name loadbalancer --kubeconfig kubeconfig.yaml
```

After installing a component we can check that out with 

```CLI
$ kubectl describe ingress pc-dev-ingress -n=kube-system --kubeconfig kubeconfig.yaml
```

## Setting up Kubernetes Dashboard
After we installed helm we can easily use both to install kubernetes dashboard

```CLI
$ helm install stable/kubernetes-dashboard --name dashboard --kubeconfig="kubeconfig.yaml" --namespace="kube-system"
```

But before we can login to tiller we need a token, we can get one of those trough the secrets. Get yourself a secret list by running the following command
```CLI
$ kubectl -n kube-system get secret  --kubeconfig="kubeconfig.yaml"
```

Because we just bound tiller to our admin account and use tiller (trough helm) to manage our code deployment it makes sense to use the tiller token, lets look at the tiller secret (it should look something like "tiller-token-XXXXX" and ask for the corresponding token. 

```CLI
$ kubectl -n kube-system describe secrets tiller-token-xxxxx  --kubeconfig="kubeconfig.yaml"
```

This should return the token, copy it to somewhere save (just the token not the other returned information) and start up a dashboard connection

```CLI
$ kubectl proxy --kubeconfig kubeconfig.yaml
```

This should proxy our dashboard to helm making it available trough our favorite browser and a simple link
```CLI
http://localhost:8001/api/v1/namespaces/kube-system/services/https:dashboard-kubernetes-dashboard:https/proxy/#!/login
```

Then, you can login using the Kubeconfig option and uploading your kubeconfig.

## Deploying trough helm
First we always need to update our dependencies
```CLI
$ kubectl apply --validate=false -f https://raw.githubusercontent.com/jetstack/cert-manager/release-0.12/deploy/manifests/00-crds.yaml --kubeconfig="kubeconfig.yaml"
$ kubectl create namespace cert-manager --kubeconfig="kubeconfig.yaml"
```
 
 The we need tp deploy the cert manager to our cluster
 
```CLI
$ helm repo add jetstack https://charts.jetstack.io
$ helm install --name cert-manager --namespace cert-manager --version v0.12.0 \ jetstack/cert-manager --kubeconfig="kubeconfig.yaml"
```

Then we need to set up the desired namespaces
```CLI
$ kubectl create namespace dev
$ kubectl create namespace stag
$ kubectl create namespace prod
```

If you want to create a new instance
```CLI
$ helm install --name pc-dev ./api/helm  --kubeconfig="api/helm/kubeconfig.yaml" --namespace=dev  --set settings.env=dev,settings.debug=1
$ helm install --name pc-stag ./api/helm --kubeconfig="api/helm/kubeconfig.yaml" --namespace=stag --set settings.env=stag,settings.debug=0
$ helm install --name pc-prod ./api/helm --kubeconfig="api/helm/kubeconfig.yaml" --namespace=prod --set settings.env=prod,settings.debug=0
```
This will create an instance by the name of pc-dev (line 1) pc-stag (line 2) or pc-prod (line 3) on your cluster, with the environment, debug and cache settings configured (see [helm settings](INSTALLATION.md#helm-settings) for more information). 

Or update if you want to update an existing one
```CLI
$ helm upgrade pc-dev ./api/helm  --kubeconfig="api/helm/kubeconfig.yaml" --namespace=dev  --set settings.env=dev,settings.debug=1
$ helm upgrade pc-stag ./api/helm --kubeconfig="api/helm/kubeconfig.yaml" --namespace=stag --set settings.env=stag,settings.debug=0
$ helm upgrade pc-prod ./api/helm --kubeconfig="api/helm/kubeconfig.yaml" --namespace=prod --set settings.env=prod,settings.debug=0
```

Or just restart the containers of the component
```CLI
$ helm del pc-dev  --purge --kubeconfig="api/helm/kubeconfig.yaml" 
$ helm del pc-stag --purge --kubeconfig="api/helm/kubeconfig.yaml" 
$ helm del pc-prod --purge --kubeconfig="api/helm/kubeconfig.yaml" 
```

Note that you can replace common ground with the namespace that you want to use (normally the name of your component).

## Helm settings
When installing components there is a number of settings that can be edited to modify the working of your component. The most important of these settings are:

- ```settings.env```: This setting influences primarily the container to be used. There are three regular possibilities: ```dev```, ```stag``` and ```prod```. 
   - ```dev``` will load the latest new container, which can be unstable because this is the version that is developed on.
   - ```stag``` will load the latest semi-stable version of the container, this setting is recommended for acceptation environments
   - ```prod``` will load the ```latest``` images, which are the latest stable version. This setting is recommended for production environments 
- ```settings.debug```: This setting can enable the extensive debugging tools included in Symfony. This is recommended in development environments by setting it to 1. However, debugging takes a lot of power from your cluster, so we recommend to switch it off in production or acceptation environments (by setting it to 0)
- ```settings.cache```: This setting can enable caching in your component. This means that traffic can be prevented by checking if a resource has already been requested and if it is still in cache. However, this means also that a version of a resource can be loaded that has been changed on the source. Therefore we recommend to switch this off in development environments (by setting this option to 0) and enable (by setting this option to 1) it on production environments to enhance the response times of the component.
- ```settings.web```: This setting determines if the component has to be exposed to the outside world. Setting it to 0 will not expose your component outside of the cluster (recommended), switching it to 1 will expose your component to ingress (recommended for front-end applications).

## Making your app known on NLX
The proto component comes with an default NLX setup, if you made your own component however you might want to provide it trough the [NLX](https://www.nlx.io/) service. Fortunately the proto component comes with an nice setup for NLX integration.

First of all change the necessary lines in the [.env](.env) file, basically everything under the NLX setup tag. Keep in mind that you wil need to have your component available on an (sub)domain name (a simple IP wont sufice).

To force the re-generation of certificates simply delete the org.crt en org.key in the api/nlx-setup folder.


## Setting up analytics and a help chat function
As a developer you might be interested to know how your application documentation is used, so you can see which parts of your documentation are most read and which parts might need some additional love. You can measure this (and other user interactions) with google tag manager. Just add your google tag id to the .env file (replacing the default) under GOOGLE_TAG_MANAGER_ID. This will only enable Google analytics on your documentation page, it will never analyse the actual traffic of the API.

Have you seen our sweet support-chat on the documentation page? We didn't build that ourselves ;). We use a Hubspot chat for that, just head over to Hubspot, create an account and enter your Hubspot embed code in het .env file (replacing the default) under HUBSPOT_EMBED_CODE.

Would you like to use a different analytics or chat-tool? Just shoot us a [feature request](https://github.com/ConductionNL/commonground-component/issues/new?assignees=&labels=&template=feature_request.md&title=New%20Analytics%20or%20Chat%20provider)!  
